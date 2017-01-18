<?php
require  __DIR__."/vendor/autoload.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

use Silex\Application;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;

//url generator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

//connection
$app['connection'] = [
    'driver'    =>  'pdo_mysql',
    'host'      =>  'localhost',
    'user'      =>  'root',
    'password'  =>  'root',
    'dbname'    =>  'blog_silex'
];

//doctrine
$app['doctrine_config'] = Setup::createYAMLMetadataConfiguration([__DIR__ . "/src/Config"], true);
$app['em'] = function ($app) {
    return EntityManager::create($app['connection'], $app['doctrine_config']);
};

//twig
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' =>  'src/Views'
]);

//form
$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

//session
$app->register(new Silex\Provider\SessionServiceProvider());

//mail
$app['swiftmailer.options'] = array(
    'host' => '127.0.0.1',
    'port' => '1025',
    'username' => '',
    'password' => '',
    'encryption' => null,
    'auth_mode' => null
);
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => $app['swiftmailer.options']
));

//security + menu
$app->before(function (Request $request) use ($app) {
    //menu
    $repository = $app['em']->getRepository('BLOG\\Models\\Page');
    $nav = $repository->findBy(array(), array('position' => 'ASC'));
    $app['twig']->addGlobal('nav', $nav);

    //security
    $route = explode('/',$request->getPathInfo());
    $route = array_slice($route, 1, 2);

    $user = $app['session']->get('user');

    if($route[0] == 'admin' && $route[1] != 'login') {
        if($user == null) {
            return $app->redirect($app['url_generator']->generate('homepage'));
        }
        $app['twig']->addGlobal('username', $user->getUsername());
    }
    elseif ($route[0] == 'admin') {
        if($user != null) {
            return $app->redirect($app['url_generator']->generate('adminHome'));
        }
    }
});

//routes
    //pages
    $app->get('/', 'BLOG\\Controllers\\PagesController::page')                          //home
        ->value('id', 1)
        ->bind('homepage');
    $app->get('/pages/{slug}-{id}', 'BLOG\\Controllers\\PagesController::page')         //other pages
        ->assert('slug', '[a-zA-Z0-9-]+')
        ->assert('id', '\d+')
        ->bind('page');
    $app->get('/contact', 'BLOG\\Controllers\\PagesController::contact')                 //contact
        ->bind('contact');
    $app->post('/contact', 'BLOG\\Controllers\\PagesController::contact');               //contact post

    //posts
    $app->get('/posts', 'BLOG\\Controllers\\PostsController::listPosts')                 //posts homepage
        ->value('page', 1)
        ->bind('blogHomepage');
    $app->get('/posts/{page}', 'BLOG\\Controllers\\PostsController::listPosts')          //posts page x
        ->bind('blogPage')
        ->assert('page', '^[1-9]\d*$');
    $app->get('/post/{id}', 'BLOG\\Controllers\\PostsController::readPost')             //read post
        ->bind('readPost')
        ->assert('id', '\d+');
    $app->post('/post/{id}', 'BLOG\\Controllers\\PostsController::readPost')             //read post post comment
        ->assert('id', '\d+');

    //admin
    $app->get('/admin/login', 'BLOG\\Controllers\\AdminController::login')                      //login page
        ->bind('login');
    $app->post('/admin/login', 'BLOG\\Controllers\\AdminController::login')                     //login page post
        ->bind('loginPost');
    $app->get('/admin/logout', 'BLOG\\Controllers\\AdminController::logout')                    //logout page
        ->bind('logout');
    $app->get('/admin/home', 'BLOG\\Controllers\\AdminController::home')                        //home
        ->bind('adminHome');

        //pages
        $app->get('/admin/list-pages', 'BLOG\\Controllers\\PagesController::pagesList')         //list pages
            ->bind('adminPagesList');
        $app->get('/admin/edit-page/{id}', 'BLOG\\Controllers\\PagesController::editPage')      //editPage
            ->assert('id', '\d+')
            ->bind('adminPageEdit');
        $app->post('/admin/edit-page/{id}', 'BLOG\\Controllers\\PagesController::editPage')     //editPage post
            ->assert('id', '\d+');
        $app->get('/admin/new-page', 'BLOG\\Controllers\\PagesController::editPage')            //editPage new
            ->value('id', 0)
            ->bind('adminPageNew');
        $app->get('/admin/delete-page/{id}', 'BLOG\\Controllers\\PagesController::deletePage')   //delete page
            ->assert('id', '^[1-9]\d*$')
            ->bind('adminPageDelete');
        $app->get('/admin/change-page-position/{id}-{action}', 'BLOG\\Controllers\\PagesController::changePagePosition') // change paeg position
            ->assert('id', '^[1-9]\d*$')
            ->assert('action', '^[1-2]$')
            ->bind('changePagePosition');

        //posts
        $app->get('/admin/list-posts', 'BLOG\\Controllers\\PostsController::postsList')         //list posts
            ->value('page', 1)
            ->bind('adminPostsListMain');
        $app->get('/admin/list-posts/{page}', 'BLOG\\Controllers\\PostsController::postsList')  //list posts page
            ->assert('page', '\d+')
            ->bind('adminPostsList');
        $app->get('/admin/edit-post/{id}', 'BLOG\\Controllers\\PostsController::editPost')      //edit post
            ->assert('id', '\d+')
            ->bind('adminPostEdit');
        $app->get('/admin/new-post', 'BLOG\\Controllers\\PostsController::editPost')            //edit post new
            ->value('id', -1)
            ->bind('adminPostNew');
        $app->post('/admin/edit-post/{id}', 'BLOG\\Controllers\\PostsController::editPost')     //edit post new post
            ->assert('id', '-?[0-9]+')
            ->bind('adminPostPost');
        $app->get('/admin/delete-post/{id}', 'BLOG\\Controllers\\PostsController::deletePost')  //delete post
            ->assert('id', '\d+')
            ->bind('adminDeletePost');
        $app->get('/admin/change-post-is-a-draft/{id}', 'BLOG\\Controllers\\PostsController::toggleIsADraft')   // change post is a draft
            ->assert('id', '\d+')
            ->bind('adminPostToggleIsADraft');

        //comments
        $app->get('/admin/list-comments/{id}', 'BLOG\\Controllers\\CommentsController::listComments')           //post list comment
            ->assert('id', '\d+')
            ->bind('adminPostCommentsList');
        $app->get('/admin/edit-comment/{id}', 'BLOG\\Controllers\\CommentsController::editComment')             //post list comment
            ->assert('id', '\d+')
            ->bind('adminPostEditComment');
        $app->post('/admin/edit-comment/{id}', 'BLOG\\Controllers\\CommentsController::editComment')            //post list comment post
            ->assert('id', '\d+')
            ->bind('adminPostEditCommentPost');
        $app->get('/admin/delete-comment/{id}', 'BLOG\\Controllers\\CommentsController::deleteComment')         //post list comment
            ->assert('id', '\d+')
            ->bind('adminPostDeleteComment');

        //messages
        $app->get('admin/list-messages', 'BLOG\\Controllers\\ContactMessagesController::messageList')           // list messages main
            ->value('page', 1)
            ->bind('adminMessagesListMain');
        $app->get('admin/list-messages/{page}', 'BLOG\\Controllers\\ContactMessagesController::messageList')    // list messages
            ->assert('page', '\d+')
            ->bind('adminMessagesList');
        $app->get('admin/view-message/{id}', 'BLOG\\Controllers\\ContactMessagesController::viewMessage')       // view message
            ->assert('id', '\d+')
            ->bind('adminViewMessage');
        $app->post('admin/reply-message/{id}', 'BLOG\\Controllers\\ContactMessagesController::viewMessage')     // reply to a message
            ->assert('id', '\d+')
            ->bind('adminReplyMessage');
        $app->get('admin/toggle-message-state/{id}', 'BLOG\\Controllers\\ContactMessagesController::changeMessageState') // change message state
            ->assert('id', '\d+')
            ->bind('adminToggleMessageState');
        $app->get('admin/all-messages-read', 'BLOG\\Controllers\\ContactMessagesController::allMessagesRead')   // posts make all messages read
            ->bind('adminAllMessagesRead');

        // edit account
        $app->get('admin/edit-account', 'BLOG\\Controllers\\AdminController::editAccount')                      // edit account
            ->bind('adminEditAccount');
        $app->post('admin/edit-account', 'BLOG\\Controllers\\AdminController::editAccount')                     // edit account post
            ->bind('adminEditAccountPost');

//run
$app->run();