<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 13/01/2017
 * Time: 21:37
 */

namespace BLOG\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Silex\Application;

class ContactMessagesController
{
    private $adminMessagesPerPage = 10;

    function messageList($page, Application $app) {
        $em = $app['em'];

        // on calcul l'offset pour avoir plusieurs pages
        $offset = ($page - 1) * $this->adminMessagesPerPage;

        // on recupère les articles
        $repository = $em->getRepository('BLOG\\Models\\ContactMessage');
        $messages = $repository->findBy(
            array(),
            array('date' => 'DESC'),
            $this->adminMessagesPerPage,
            $offset
        );

        // on calcul le numero maximum d'une page pour la pagination
        $pageMax = $repository->createQueryBuilder('cm')
            ->select('count(cm.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $pageMax = ceil($pageMax / $this->adminMessagesPerPage);

        return $app['twig']->render('admin/messages/list.twig', ['messages'  =>  $messages, 'currentPage' => $page, 'pageMax' => $pageMax]);
    }

    function viewMessage($id, Request $request, Application $app) {
        $em = $app['em'];

        // on recupère le message
        $message = $em->find('BLOG\\Models\\ContactMessage', $id);

        //si le message n'est pas vu alors on le marque comme vu
        if($message->getViewed() == false) {
            $message->setViewed(true);

            $em->persist($message);
            $em->flush();
        }

        $data = array('id' => $id);
        // creation du formulaire
        $form = $app['form.factory']->createBuilder(FormType::class, $data)
            ->setAction($app['url_generator']->generate('adminReplyMessage', array('id' => $id)))
            ->setMethod('POST')
            ->add('titre')
            ->add('contenu', 'textarea')
            ->add('id', 'hidden')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);
        // on a une réponse
        if ($form->isValid()) {
            $data = $form->getData();

            // on créé un nouveau mail via swift message
            $mail = \Swift_Message::newInstance()
                ->setSubject($data['titre'])
                ->setFrom(array('contact@local.dev'))
                ->setTo(array($message->getMail()))
                ->setBody($data['contenu']);

            // envoi du mail
            $mailer = $app['mailer'];
            $mailer->send($mail);

            $app['session']->getFlashBag()->add('info', 'Mail envoyé');
        }

        return $app['twig']->render('admin/messages/view.twig', array('message' => $message, 'form' => $form->createView()));
    }

    function changeMessageState($id, Application $app) {
        $em = $app['em'];

        // recuperation du message
        $message = $em->find('BLOG\\Models\\ContactMessage', $id);

        // si le message n'existe pas
        if($message == null) {
            $app['session']->getFlashBag()->add('danger', 'Message non trouvé');
            return $app->redirect($app['url_generator']->generate('adminMessagesListMain'));
        }

        // on inverse l'etat du message
        if($message->getViewed()) {
            $message->setViewed(false);
        }
        else {
            $message->setViewed(true);
        }

        // sauvegarde
        $em->persist($message);
        $em->flush();

        $app['session']->getFlashBag()->add('info', 'Changement effectué');
        return $app->redirect($app['url_generator']->generate('adminMessagesListMain'));
    }

    function allMessagesRead(Application $app) {
        $em = $app['em'];

        // on utilise querybuilder pour mettre tous les messages non lu a lu
        $repository = $em->getRepository('BLOG\\Models\\ContactMessage');
        $query = $repository->createQueryBuilder('cm')
            ->update()
            ->set('cm.viewed', 1)
            ->where('cm.viewed = 0')
            ->getQuery();

        $query->execute();

        $app['session']->getFlashBag()->add('flash', 'Messages marqués comme lu');
        return $app->redirect($app['url_generator']->generate('adminMessagesListMain'));
    }
}