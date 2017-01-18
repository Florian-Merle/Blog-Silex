<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 07/01/2017
 * Time: 09:22
 */

namespace BLOG\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class AdminController
{
    function login(Request $request, Application $app) {
        $em = $app['em'];

        // creation du form
        $form = $app['form.factory']->createBuilder(FormType::class, array())
            ->setAction($app['url_generator']->generate('loginPost'))
            ->setMethod('POST')
            ->add('pseudo')
            ->add('mot_de_passe', 'password')
            ->add('Connexion', 'submit')
            ->getForm();


        $form->handleRequest($request);

        // si on a une réponse via le formulaire
        if ($form->isValid()) {
            $data = $form->getData();

            // on chercher un user qui correspond à ce qui est passé
            $user = $em->getRepository('BLOG\\Models\\User')->findBy(array(
                'username'  =>  $data['pseudo'],
                'password'  =>  $this->cryptPass($data['mot_de_passe'])
            ));

            if($user != null) { // connection
                $app['session']->set('user', $user[0]); // on enregistre en session le user
                return $app->redirect($app['url_generator']->generate('adminHome'));
            }
            else {
                $app['session']->getFlashBag()->add('danger', 'Identifiants incorrects');
                return $app->redirect($app['url_generator']->generate('login'));
            }
        }

        return $app['twig']->render('admin/login.twig', array('form' => $form->createView()));
    }

    function logout(Application $app) {
        $app['session']->remove('user'); // on detruit le contenu de la variable de session 'user'
        return $app->redirect($app['url_generator']->generate('login'));
    }

    function home(Application $app) {
        $em = $app['em'];

        //recuperation des pages
        $pagesRepository = $em->getRepository('BLOG\\Models\\Page');
        $pages = $pagesRepository->findBy(
            array(),
            array('position' => 'ASC'),
            null,
            null
        );

        //recuperations des 4 derniers posts
        $postsRepository = $em->getRepository('BLOG\\Models\\Post');
        $posts = $postsRepository->findBy(
            array(),
            array('date' => 'DESC'),
            4,
            null
        );

        //recuperation des 5 derniers comments
        $commentsRepository = $em->getRepository('BLOG\\Models\\Comment');
        $comments = $commentsRepository->findBy(
            array(),
            array('date' => 'DESC'),
            5,
            null
        );

        // recupération des messages non lu
        $messagesRepository = $em->getRepository('BLOG\\Models\\ContactMessage');
        $messages = $messagesRepository->createQueryBuilder('cm')
            ->select('cm')
            ->where('cm.viewed = 0')
            ->orderby('cm.date', 'DESC')
            ->getQuery()
            ->getResult();

        return $app['twig']->render('admin/home.twig', array(
            'pages' => $pages,
            'posts' => $posts,
            'comments' => $comments,
            'messages' => $messages
        ));
    }

    function editAccount(Request $request, Application $app) {
        $em = $app['em'];

        // creation du formulaire
        $form = $app['form.factory']->createBuilder(FormType::class, array())
            ->setAction($app['url_generator']->generate('adminEditAccount'))
            ->setMethod('POST')
            ->add('ancien_mot_de_passe', 'password')
            ->add('nouveau_mot_de_passe', 'password')
            ->add('confirmation', 'password')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);

        //si on a une réponse
        if ($form->isValid()) {
            $data = $form->getData();

            //si nouveau mdp vide
            if($data['nouveau_mot_de_passe'] == '') {
                $app['session']->getFlashBag()->add('danger', 'Le mot de passe ne peux pas être vide');
                return $app->redirect($app['url_generator']->generate('adminEditAccount'));
            }

            // si les 2 nouveau mdp ne sont pas les memes
            if($data['nouveau_mot_de_passe'] != $data['confirmation']) {
                $app['session']->getFlashBag()->add('danger', 'Les nouveaux mots de passe ne concordent pas');
                return $app->redirect($app['url_generator']->generate('adminEditAccount'));
            }

            // on recupere l'ancien mot de passe
            $id = $app['session']->get('user')->getId();
            $user = $em->find('BLOG\\Models\\User', $id);

            // si l'ancien mdp rentré dans le form n'est pas bon
            if($user->getPassword() != $this->cryptPass($data['ancien_mot_de_passe'])) {
                $app['session']->getFlashBag()->add('danger', 'L\'ancien mot de passe n\'est pas bon');
                return $app->redirect($app['url_generator']->generate('adminEditAccount'));
            }

            // on met a jour le nouveau mdp
            $user->setPassword($this->cryptPass($data['nouveau_mot_de_passe']));
            $em->persist($user);
            $em->flush();

            //deconnection
            $app['session']->getFlashBag()->add('info', 'Mot de passe modifié!');
            return $app->redirect($app['url_generator']->generate('logout'));
        }

        return $app['twig']->render('admin/editAccount.twig', array('form' => $form->createView()));
    }

    function cryptPass($pass) { // on crypte le mdp
        return md5(sha1($pass));
    }
}