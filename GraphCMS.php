<?php
    namespace GraphCMS;
    use \Exception;

    interface Builder {
        function build() : string;
    }

    /**
     * The Part class handles building a single
     * part of a command (such as field list or param value
     */
    class Part implements Builder {
        const TYPE_FIELDLIST = 0;
        const TYPE_PARAM = 1;

        /** @var int Contains the part type */
        protected $_type = 0;
        /** @var mixed Contains the Part value */
        protected $_value;

        /**
         * @param mixed $value
         */
        public function __construct($value, int $type = 0) {
            $this->_value = $value;
            $this->_type = $type;
        }

        /**
         * Sets and overrides the value
         *
         * @param mixed $value
         */
        public function setValue($value) {
            $this->_value = $value;
        }

        /**
         * Expands an array to query string
         *
         * @return string
         */
        protected function expand(array $value) : string {
            $start = ($this->_type==Part::TYPE_FIELDLIST||array_keys($value)!==range(0, count($value) - 1)) ? '{' : '[';
            $output = $start;
            foreach($value as $k => $v) {
                if(!is_numeric($k)) {
                    $output .= $k;
                    if($this->_type!=Part::TYPE_FIELDLIST||(!is_array($v)&&!$v instanceof Builder)) {
                        $output .= ':';
                    }
                }
                if(is_array($v)) {
                    $output.=$this->expand($v);
                } else if(is_string($v)&&!is_numeric($k)) {
                    $output.='"'.addslashes($v).'"';
                } else if($v instanceof Builder) {
                    $output .= $v->build();
                } else {
                    $output .= print_r($v, true);
                }
                $output .= ',';
            }
            return trim($output, ',').($start=='{' ? '}' : ']');
        }

        /**
         * Builds the part as a query string
         *
         * @return string
         */
        public function build() : string {
            if(is_array($this->_value)) {
                return $this->expand($this->_value);
            }
            return print_r($this->_value, true);
        }

        /**
         * @return string
         */
        public function __toString() : string {
            return $this->build();
        }
    }

    class Command implements Builder {
        const TYPE_ALL = 'all';
        const TYPE_CREATE = 'create';
        const TYPE_UPDATE = 'update';
        const TYPE_UPDATE_CREATE = 'updateOrCreate';
        const TYPE_REMOVE = 'remove';

        /** @var string The type of the command (all, create, update, remove, ...) */
        protected $_type = '';
        /** @var string the name of the command model */
        protected $_name = '';
        /** @var array Contains the params for the command */
        protected $_params = [];
        /** @var array Contains the fields to fetch with the command */
        protected $_fields = [];

        public function __construct(string $name, string $type = '', array $fields = [], array $params = []) {
            $this->_name = $name;
            $this->_type = $type;
            $this->addParams($params);
            $this->addFields($fields);
        }

        /**
         * Sets the type
         *
         * @param string New type
         */
        public function setType(string $type) {
            $this->_type = $type;
        }

        /**
         * Sets the name of the content model
         *
         * @param string $name
         */
         public function setName(string $name) {
             $this->_name = $name;
         }

        /**
         * Adds a parameter to the command
         *
         * @param string $key The name of the parameter key
         * @param mixed $value The value of the parameter
         */
        public function addParam(string $key, $value) {
            $this->_params[$key] = ($value instanceof Part) ? $value : new Part($value, Part::TYPE_PARAM);
        }

        /**
         * Adds some parameters
         *
         * @param array $params
         */
        public function addParams(array $params) {
            foreach($params as $k => $v) {
                $this->addParam($k, $v);
            }
        }

        /**
         * Adds a field
         *
         * @param string $field The name of the field
         * @param mixed $subfield The possible subfield
         */
        public function addField(string $field, $subfield = null) {
            if(is_array($subfield)) {
                $this->_fields[$field] = ($subfield instanceof Part) ? $subfield : new Part($subfield, Part::TYPE_FIELDLIST);
            } else {
                $this->_fields[] = $field;
            }
        }

        /**
         * Adds some fields
         *
         * @param array $fields
         */
        public function addFields(array $fields) {
            foreach($fields as $k => $v) {
                if(is_numeric($k)&&is_string($v)) {
                    $this->addField($v);
                } else if(is_string($k)&&is_array($v)) {
                    $this->addField($k, $v);
                }
            }
        }

        /**
         * Builds the command
         *
         * @return string
         */
        public function build() : string {
            $command = $this->_type.strtoupper(substr($this->_name, 0, 1)).substr($this->_name, 1);
            if($this->_type == Command::TYPE_ALL) {
                $command .= 's';
            }
            // add parameters if not empty
            if(!empty($this->_params)) {
                $command.='(';
                foreach($this->_params as $name => $value) {
                    $command.=$name.':'.$value->build().',';
                }
                $command = trim($command, ',').')';
            }
            // add field list
            if(empty($this->_fields)) {
                throw new Exception('No field list specified');
            }
            $command .= (new Part($this->_fields, Part::TYPE_FIELDLIST))->build();
            return $command;
        }

        public function __toString() : string {
            return $this->build();
        }
    }

    class Request implements Builder {
        const TYPE_QUERY = 'query';
        const TYPE_MUTATION = 'mutation';

        /** @var string The type of the command (query, mutation)? */
        protected $_type = 'query';
        /** @var array Contains the commands to execute in the request */
        protected $_commands = [];

        public function __construct(string $type, array $commands = []) {
            $this->_type = $type;
            $this->addCommands($commands);
        }

        /**
         * Sets the request type
         *
         * @param string $type
         */
        public function setType(string $type) {
            $this->_type = $type;
        }

        /**
         * Adds a command to the request
         *
         * @param mixed $nameOrCommand The name of the command or a Command object
         * @param string $type The type of the command
         * @param array $params The parameters
         * @param array $fields The fields
         */
        public function addCommand($nameOrCommand, string $type = '', array $fields = [], array $params = []) {
            if($nameOrCommand instanceof Command) {
                $this->_commands[] = $nameOrCommand;
            } else {
                $this->_commands[] = new Command($nameOrCommand, $type, $fields, $params);
            }
        }

        /**
         * Adds multiple commands
         *
         * @param array $commands Array of commands
         */
        public function addCommands(array $commands) {
            foreach($commands as $c) {
                if(is_array($c)) {
                    call_user_func_array([$this, 'addCommand'], $c);
                } else if($c instanceof Command) {
                    $this->addCommand($c);
                }
            }
        }

        /**
         * Builds the request
         *
         * @return string
         */
        public function build() : string {
            $request = $this->_type.'{';
            foreach($this->_commands as $c) {
                $request .= $c->build().',';
            }
            return trim($request, ',').'}';
        }

        /**
         * Executes the request and returns the data
         *
         * @param string $project Project ID
         * @param string $token The API token
         *
         * @return array
         */
        public function execute(string $project, string $token) : array {
            $c = curl_init('https://api.graphcms.com/simple/v1/'.$project);
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            ]);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode(['query' => $this->build()]));
            $result = curl_exec($c);
            curl_close($c);
            if($result !== false) {
                return json_decode($result, true);
            }
            return [];
        }

        public function __toString() : string {
            return $this->build();
        }
    }