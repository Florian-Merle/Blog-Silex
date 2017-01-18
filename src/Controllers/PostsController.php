<?php

namespace BLOG\Controllers;

use BLOG\Models\Comment;
use BLOG\Models\Post;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class PostsController {
    private $postsPerPage = 4;

    function listPosts($page, Application $app) {
        $em = $app['em'];

        // on calcul l'offset pour la pagination
        $offset = ($page - 1) * $this->postsPerPage;

        // on recupère les posts qui ne sont pas en mode brouillon dans l'ordre de la date
        $repository = $em->getRepository('BLOG\\Models\\Post');
        $posts = $repository->findBy(
            array('isadraft' => 0),
            array('date' => 'DESC'),
            $this->postsPerPage,
            $offset
        );

        // on recupère la page max pour la pagination
        $pageMax = $repository->createQueryBuilder('p')
                        ->select('count(p.id)')
                        ->getQuery()
                        ->getSingleScalarResult();
        $pageMax = ceil($pageMax / $this->postsPerPage);

        return $app['twig']->render('Posts/list.twig', ['posts'  =>  $posts, 'currentPage' => $page, 'pageMax' => $pageMax]);
    }

    function readPost($id, Application $app, Request $request) {
        $em = $app['em'];

        $post = $em->find('BLOG\\Models\\Post', $id);

        if($post == null) {
            $app['session']->getFlashBag()->add('danger', 'Article non trouvée');
            return $app->redirect($app['url_generator']->generate('blogHomepage'));
        }

        // on créé un formulaire pour les commentaires
        $form = $app['form.factory']->createBuilder(FormType::class, array())
            ->setAction($app['url_generator']->generate('readPost', array('id' => $id)))
            ->setMethod('POST')
            ->add('pseudo')
            ->add('message', 'textarea')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);

        // si on a une réponse
        if ($form->isValid()) {
            $data = $form->getData();

            // verification des champs
            if($data['pseudo'] == '' || $data['message'] == '') {
                $app['session']->getFlashBag()->add('flash', 'Les champs ne peuvent pas être vide');

                return $app->redirect($app['url_generator']->generate('readPost', array('id' => $id)));
            }

            // on créé le nouveau commentaire
            $comment = new Comment(
                $post,
                $data['pseudo'],
                $data['message']
            );

            // enregistrement du commentaire
            $em->persist($comment);
            $em->flush();

            $app['session']->getFlashBag()->add('info', 'Message envoyé!');

            return $app->redirect($app['url_generator']->generate('readPost', array('id' => $id)));
        }

        return $app['twig']->render('Posts/post.twig', ['post' => $post, 'form' => $form->createView()]);
    }

    // admin

    private $adminPostsPerPage = 10;

    function postsList($page, Application $app) {
        $em = $app['em'];

        // on calcul l'offset pour la pagination
        $offset = ($page - 1) * $this->adminPostsPerPage;

        // on recupère les commentaires
        $repository = $em->getRepository('BLOG\\Models\\Post');
        $posts = $repository->findBy(
            array(),
            array('date' => 'DESC'),
            $this->adminPostsPerPage,
            $offset
        );

        // on recupère la page max pour la pagination
        $pageMax = $repository->createQueryBuilder('p')
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $pageMax = ceil($pageMax / $this->adminPostsPerPage);


        return $app['twig']->render('admin/posts/list.twig', ['posts'  =>  $posts, 'currentPage' => $page, 'pageMax' => $pageMax]);
    }

    function editPost($id, Request $request, Application $app) {
        $em = $app['em'];

        // si on créé un nouveau post par default on l'enregistre en brouillon
        $data = array('id' => 0, 'enregistrer_en_tant_que_brouillon' => true);
        $post = null;

        if ($id != -1) { // on edite un post qui existe deja
            $post = $em->find('BLOG\\Models\\Post', $id); // on recupère le post

            // si le post n'existe pas
            if($post == null) {
                $app['session']->getFlashBag()->add('danger', 'Article non trouvé!');
                return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
            }

            // on met dans le formulaire les informations du post
            $data = array(
                'titre'     =>  $post->getTitle(),
                'enregistrer_en_tant_que_brouillon' => $post->getIsadraft(),
                'contenu'   =>  $post->getContent(),
                'id'        =>  $id
            );
        }

        // creation du form
        $form = $app['form.factory']->createBuilder(FormType::class, $data)
            ->setAction($app['url_generator']->generate('adminPostPost', array('id' => $id)))
            ->setMethod('POST')
            ->add('titre')
            ->add('enregistrer_en_tant_que_brouillon', 'checkbox')
            ->add('contenu', 'textarea')
            ->add('id', 'hidden')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);

        // on a une reponse
        if ($form->isValid()) {
            $data = $form->getData();

            // si un champ est vide
            if($data['titre'] == '' || $data['contenu'] == '') {
                $app['session']->getFlashBag()->add('danger', 'Les champs ne peuvent pas être vide');
                if($id == -1) {
                    return $app->redirect($app['url_generator']->generate('adminPostNew'));
                }
                return $app->redirect($app['url_generator']->generate('adminPostEdit', array('id' => $id)));
            }

            if($id != -1) { // post existant
                // on change les données du post
                $post->setTitle($data['titre']);
                $post->setContent($data['contenu']);
                $post->setIsadraft($data['enregistrer_en_tant_que_brouillon']);

                // enregistrement
                $em->persist($post);
                $em->flush();
            }
            else {
                $user = $app['session']->get('user');

                // on créé un nouveau post
                $post = new Post(
                    $data['titre'],
                    $data['contenu'],
                    $user,
                    $data['enregistrer_en_tant_que_brouillon']
                );

                // enregistrement
                $em->merge($post);
                $em->flush();
            }

            $app['session']->getFlashBag()->add('info', 'Article validée!');
            return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
        }

        return $app['twig']->render('admin/posts/edit.twig', array('form' => $form->createView(), 'post' => $post));
    }

    function deletePost($id, Application $app) {
        $em = $app['em'];

        // on recupère le post
        $post = $em->find('BLOG\\Models\\Post', $id);

        // si le post n'existe pas
        if($post == null) {
            $app['session']->getFlashBag()->add('danger', 'Article non trouvé!');
            return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
        }

        // on supprime le post dans la bdd
        $em->remove($post);
        $em->flush();

        $app['session']->getFlashBag()->add('info', 'Article supprimé!');
        return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
    }

    function toggleIsADraft($id, Application $app) {
        $em = $app['em'];

        // on recupère le post
        $post = $em->find('BLOG\\Models\\Post', $id);

        // si le post n'existe pas
        if($post == null) {
            $app['session']->getFlashBag()->add('danger', 'Article non trouvé!');
            return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
        }

        // on inverse l'état du post
        if($post->getIsadraft() == true) {
            $post->setIsadraft(false);
        }
        else {
            $post->setIsadraft(true);
        }

        // enregistrement
        $em->persist($post);
        $em->flush();

        $app['session']->getFlashBag()->add('info', 'Changement effectué!');
        return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
    }
}