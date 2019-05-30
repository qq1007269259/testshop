<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
// set('application', 'my_project');

// Project repository
set('repository', 'https://github.com/qq1007269259/laravel-shop-advanced.git');
set('shared_files', []);
set('shared_dirs', []);
set('writable_dirs', []);
// 顺便把 composer 的 vendor 目录也加进来
add('copy_dirs', ['node_modules', 'vendor']);

host('39.97.231.130')
    ->user('root') // 使用 root 账号登录
    ->identityFile('~/.ssh/laravel-shop-aliyun.pem') // 指定登录密钥文件路径
    ->become('www-data') // 以 www-data 身份执行命令
    ->set('deploy_path', '/var/www/laravel-shop-deployer'); // 指定部署目录
    
host('39.97.191.147')
    ->user('root')
    ->identityFile('~/.ssh/laravel-shop-aliyun.pem')
    ->become('www-data')
    ->set('deploy_path', '/var/www/laravel-shop'); // 第二台的部署目录与第一台不同
// 定义一个上传 .env 文件的任务
desc('Upload .env file');
task('env:upload', function() {
    // 将本地的 .env 文件上传到代码目录的 .env
    upload('.env', '{{release_path}}/.env');
});
// 定义一个前端编译的任务
desc('Yarn');
task('deploy:yarn', function () {
    // release_path 是 Deployer 的一个内部变量，代表当前代码目录路径
    // run() 的默认超时时间是 5 分钟，而 yarn 相关的操作又比较费时，因此我们在第二个参数传入 timeout = 600，指定这个命令的超时时间是 10 分钟
    run('cd {{release_path}} && SASS_BINARY_SITE=http://npm.taobao.org/mirrors/node-sass yarn && yarn production', ['timeout' => 600]);
});

// 定义一个 执行 es:migrate 命令的任务
desc('Execute elasticsearch migrate');
task('es:migrate', function() {
    // {{bin/php}} 是 Deployer 内置的变量，是 PHP 程序的绝对路径。
    run('{{bin/php}} {{release_path}}/artisan es:migrate');
})->once();

desc('Restart Horizon');
task('horizon:terminate', function() {
    run('{{bin/php}} {{release_path}}/artisan horizon:terminate');
});


// 定义一个后置钩子，在 deploy:shared 之后执行 env:upload 任务
after('deploy:shared', 'env:upload');
// 定义一个后置钩子，在 deploy:vendors 之后执行 deploy:yarn 任务
after('deploy:vendors', 'deploy:yarn');
after('deploy:failed', 'deploy:unlock');
// 在 deploy:vendors 之前调用 deploy:copy_dirs
// 定义一个后置钩子，在 artisan:migrate 之后执行 es:migrate 任务
after('artisan:migrate', 'es:migrate');
// 在 deploy:symlink 任务之后执行 horizon:terminate 任务
after('deploy:symlink', 'horizon:terminate');
before('deploy:vendors', 'deploy:copy_dirs');
before('deploy:symlink', 'artisan:migrate');
// [Optional] Allocate tty for git clone. Default value is false.
// set('git_tty', true); 

// Shared files/dirs between deploys 

// Writable dirs by web server 


// Hosts

// host('project.com')
//     ->set('deploy_path', '~/{{application}}');    
    
// Tasks

// task('build', function () {
//     run('cd {{release_path}} && build');
// });

// [Optional] if deploy fails automatically unlock.

// Migrate database before symlink new release.


