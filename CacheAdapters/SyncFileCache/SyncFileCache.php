<?php
    namespace GraphCMS\CacheAdapters\SyncFileCache;
    use GraphCMS\Query;
    use GraphCMS\CacheAdapter;

    class SyncFileCache extends CacheAdapter {
        /** @var string Where to save the caching files */
        protected $_cacheDir;
        /** @var int How many seconds counts as expired */
        protected $_expiration;

        /**
         * @param string $cacheDir Where to save the cache files
         */
        public function __construct(string $cacheDir, int $expiration = 3600) {
            $this->_cacheDir = $cacheDir;
            $this->_expiration = $expiration;
            // check dir
            if(!is_dir($this->_cacheDir)) {
                mkdir($this->_cacheDir, 0755, true);
            }
        }

        /**
         * For getting a value from cache
         *
         * @param string $query
         * @param array $default
         * @return array
         */
        public function get(string $query, bool &$expired = null) : array {
            $hash = sha1($query);
            $path = $this->_cacheDir.'/'.$hash.'.json';
            // get from cachedir
            if(is_file($path)) {
                $data = json_decode(file_get_contents($path), true);
                // check expiration
                $diff = time() - $data['updated'];
                $expired = ($diff > $this->_expiration);
                // return data
                return $data['data'];
            }
            // return empty if no data availble
            return [];
        }

        /**
         * For directly setting a value in cache
         *
         * @param string $query
         * @param array $data
         * @return bool
         */
        public function set(string $query, array $data) : bool {
            $hash = sha1($query);
            $path = $this->_cacheDir.'/'.$hash.'.json';
            // write to file immediately
            return file_put_contents($path, json_encode([
                'updated' => time(),
                'data' => $data
            ]));
        }

        /**
         * For updating a value in cache
         * this could be using an emitter with
         * beanstalk, this could be synchronously...
         *
         * @param Query $query
         * @return bool
         */
        public function update(Query $query, array $values = []) : bool {
            return $this->set($query->build(), $query->execute($values));
        }
    }