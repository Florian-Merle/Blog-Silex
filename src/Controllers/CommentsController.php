<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 13/01/2017
 * Time: 21:35
 */

namespace BLOG\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Silex\Application;

class CommentsController
{
    //admin
    function listComments($id, Application $app) {
        $em = $app['em'];

        //on recupere les commentaires d'un article particulier
        $post = $em->find('BLOG\\Models\\Post', $id);

        return $app['twig']->render('admin/posts/comments.twig', array('post' => $post));
    }

    function editComment($id, Request $request, Application $app) {
        $em = $app['em'];

        $comment = $em->find('BLOG\\Models\\Comment', $id); //on recupere le commentaire

        // si le commentaire n'existe pas
        if($comment == null) {
            $app['session']->getFlashBag()->add('danger', 'Commentaire non trouvé');
            return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
        }

        // on créé un formulaire
        $data = array(
            'pseudo'     =>  $comment->getUsername(),
            'message'   =>  $comment->getContent()
        );
        $form = $app['form.factory']->createBuilder(FormType::class, $data)
            ->setAction($app['url_generator']->generate('adminPostEditCommentPost', array('id' => $id)))
            ->setMethod('POST')
            ->add('pseudo')
            ->add('message', 'textarea')
            ->add('id', 'hidden')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);
        // si on a une reponse
        if ($form->isValid()) {
            $data = $form->getData();

            $comment->setUsername($data['pseudo']);
            $comment->setContent($data['message']);

            // maj du commentaire
            $em->persist($comment);
            $em->flush();

            // redirection
            $app['session']->getFlashBag()->add('info', 'Commentaire modifié!');
            return $app->redirect($app['url_generator']->generate('adminPostCommentsList', array('id' => $comment->getPost()->getId())));
        }

        return $app['twig']->render('admin/posts/editComment.twig', array('form' => $form->createView(), 'comment' => $comment));
    }

    function deleteComment($id, Application $app) {
        $em = $app['em'];

        // recuperation du commentaire
        $comment = $em->find('BLOG\\Models\\Comment', $id);

        // si le commentaire n'existe pas
        if($comment == null) {
            $app['session']->getFlashBag()->add('danger', 'Commentaire non trouvé');
            return $app->redirect($app['url_generator']->generate('adminPostsList', array('page' => 1)));
        }

        // on supprime le commentaire
        $em->remove($comment);
        $em->flush();

        $app['session']->getFlashBag()->add('info', 'Commentaire supprimé!');
        return $app->redirect($app['url_generator']->generate('adminPostCommentsList', array('id' => $comment->getPost()->getId())));
    }
}