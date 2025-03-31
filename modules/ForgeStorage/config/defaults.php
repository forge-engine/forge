<?php

return [
   'forge_storage' => [
       'default_driver' => 'local',
       'hash_filenames' => true,
       'root_path' => 'storage/app',
       'public_path' => 'public/storage',
       'buckets' => [
           'uploads' => [
               'driver' => 'local',
               'public' => false,
               'expire' => 3600 // 1 hour
           ]
       ]
   ]
];
