<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Version\GitVersionCollection;

$directory = __DIR__ . '/src';

$versions = GitVersionCollection::create($directory)
  ->add('develop', 'develop branch');

$sami = new Sami($directory, [
  'theme'                => 'default',
  'versions'             => $versions,
  'title'                => 'Klaviyo PHP API Library',
  'build_dir'            => __DIR__ . '/api/%version%',
  'cache_dir'            => '/tmp/cache/klaviyo-api-php/%version%',
  'remote_repository'    => new GitHubRemoteRepository('GollyGood/klaviyo-api-php', dirname($directory)),
  'default_opened_level' => 1,
]);

return $sami;
