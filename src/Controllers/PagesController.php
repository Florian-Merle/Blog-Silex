<?php

namespace BLOG\Controllers;

use BLOG\Models\ContactMessage;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use BLOG\Models\Page;

class PagesController {
    function page($id, $slug, Application $app) {
        $em = $app['em'];

        $page = $em->find('BLOG\\Models\\Page', $id); // on recupère la page

        if($page != null && $id != 1) { // si on trouve une page et qu'elle est différente de l'accueil
            if($slug != $page->getSlug()) { // si le slug n'est pas bon on redirige
                $url = $app['url_generator']->generate('page', array('slug' => $page->getSlug(), 'id' => $id));
                return $app->redirect($url);
            }
        }

        return $app['twig']->render('Pages/page.twig', ['page'  =>  $page]);
    }

    function contact(Request $request, Application $app) {
        // creation du form
        $form = $app['form.factory']->createBuilder(FormType::class, array())
            ->setAction($app['url_generator']->generate('contact'))
            ->setMethod('POST')
            ->add('titre')
            ->add('mail', 'email')
            ->add('message', 'textarea')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);
        // si on a une réponse
        if ($form->isValid()) {
            $em = $app['em'];

            $data = $form->getData();

            // on verrifie qu'aucun champ n'est vide
            if($data['titre'] == '' || $data['mail'] == '' || $data['message'] == '') {
                $app['session']->getFlashBag()->add('flash', 'Les champs ne peuvent pas être vide');

                return $app->redirect($app['url_generator']->generate('contact'));
            }

            // on verrifie que l'email est bien une email
            if(!preg_match('/[1-9a-zA-Z._-]+@[1-9a-zA-Z_.-]+.[a-zA-Z]+/', $data['mail'])) {
                $app['session']->getFlashBag()->add('flash', 'L\'adressse mail n\'est pas valide');

                return $app->redirect($app['url_generator']->generate('contact'));
            }

            // on crée un nouveau message et on le sauvegarde
            $msg = new ContactMessage(
                $data['titre'],
                $data['message'],
                $data['mail']
            );
            $em->persist($msg);
            $em->flush();

            $app['session']->getFlashBag()->add('flash', 'Message envoyé!');

            return $app->redirect($app['url_generator']->generate('contact'));
        }

        return $app['twig']->render('Pages/contact.twig', array('form' => $form->createView()));
    }

    // admin

    function pagesList(Application $app) {
        $em = $app['em'];

        // recupération de la liste des pages dans "l'ordre des positions"
        $repository = $em->getRepository('BLOG\\Models\\Page');
        $pages = $repository->findBy(array(), array('position' => 'ASC'));

        return $app['twig']->render('admin/pages/list.twig', array('pages' => $pages, 'positionMax' => count($pages)));
    }

    function editPage($id, Request $request, Application $app) {
        $em = $app['em'];

        $data = array('id' => 0);
        if ($id != 0) { // la page existe
            $page = $em->find('BLOG\\Models\\Page', $id); // on recupère la page

            // si la page n'est pas trouvée on redirige
            if($page == null) {
                $app['session']->getFlashBag()->add('danger', 'Page non trouvée');
                return $app->redirect($app['url_generator']->generate('adminPagesList', array('page' => 1)));
            }

            // on on met les parametres de la page dans le form
            $data = array(
                'titre'     =>  $page->getTitle(),
                'slug'      =>  $page->getSlug(),
                'contenu'   =>  $page->getContent(),
                'id'        =>  $id
            );
        }

        // creation du form
        $form = $app['form.factory']->createBuilder(FormType::class, $data)
            ->setAction($app['url_generator']->generate('adminPageEdit', array('id' => $id)))
            ->setMethod('POST')
            ->add('titre')
            ->add('slug')
            ->add('contenu', 'textarea')
            ->add('id', 'hidden')
            ->add('Envoyer', 'submit')
            ->getForm();

        $form->handleRequest($request);
        // si on a une reponse
        if ($form->isValid()) {
            $data = $form->getData();

            // si un des champ est vide
            if($data['titre'] == '' || $data['slug'] == '' || $data['contenu'] == '') {
                $app['session']->getFlashBag()->add('danger', 'Les champs ne peuvent pas être vide');
                if($id == -1) {
                    return $app->redirect($app['url_generator']->generate('adminPageNew'));
                }
                return $app->redirect($app['url_generator']->generate('adminPageEdit', array('id' => $id)));
            }

            if($id != 0) { // existing page
                $page->setTitle($data['titre']);
                $page->setSlug($data['slug']);
                $page->setContent($data['contenu']);
            }
            else { // sinon on créé une nouvelle page
                $page = new Page(
                    $data['titre'],
                    $data['slug'],
                    $data['contenu']
                );
            }

            // on enregistre
            $em->persist($page);
            $em->flush();

            $app['session']->getFlashBag()->add('info', 'Page validée!');
            return $app->redirect($app['url_generator']->generate('adminPagesList'));
        }

        return $app['twig']->render('admin/pages/edit.twig', array('form' => $form->createView()));
    }

    function deletePage($id, Application $app) {
        $em = $app['em'];

        if($id == 0) { // si c'est la page d'acceuil (normalement on ne rentre jamais dans cette condition si on regarde la route: assert)
            $app['session']->getFlashBag()->add('danger', 'Cette page ne peut pas être supprimée!');
            return $app->redirect($app['url_generator']->generate('adminPagesList'));
        }

        // recuperation de la page
        $page = $em->find('BLOG\\Models\\Page', $id);

        // la page n'existe pas
        if($page == null) {
            $app['session']->getFlashBag()->add('danger', 'La page n\'existe pas');
            return $app->redirect($app['url_generator']->generate('adminPagesList'));
        }

        // change order
        $repository = $em->getRepository('BLOG\Models\Page');
        $pages = $repository->createQueryBuilder('p')
            ->select('p')
            ->where('p.position > ' . $page->getPosition())
            ->getQuery()
            ->getResult();

        // on change les positions des pages de positions superieure
        foreach ($pages as $p) {
            $p->setPosition($p->getPosition() - 1);

            $em->persist($p);
            $em->flush();
        }

        // on supprime la page concernée
        $em->remove($page);
        $em->flush();

        $app['session']->getFlashBag()->add('info', 'Page supprimée!');
        return $app->redirect($app['url_generator']->generate('adminPagesList'));
    }

    function changePagePosition($id, $action, Application $app) { // if action = 1 then decrease, if = 2 then increase
        $em = $app['em'];

        // on recupère la page
        $page = $em->find('BLOG\\Models\\Page', $id);
        $oldPosition = $page->getPosition();

        if($page == null) { // la page n'existe pas
            $app['session']->getFlashBag()->add('danger', 'Erreur lors du changement de postion');
            return $app->redirect($app['url_generator']->generate('adminPagesList'));
        }

        // on créé la nouvelle position en fonction de l'action
        $newPosition = $oldPosition;
        if($action == 1) {
            $newPosition--;
        }
        else {
            $newPosition++;
        }

        if($oldPosition == 2 && $action == 1) { // si une page veux prendre la postion de la page d'acceuil
            $app['session']->getFlashBag()->add('danger', 'Erreur lors du changement de postion');
            return $app->redirect($app['url_generator']->generate('adminPagesList'));
        }

        // on recupère la page 2 (celle qui est echangée)
        $repository = $em->getRepository('BLOG\\Models\\Page');
        $page2 = $repository->findOneBy(
            array('position' => $newPosition),
            array()
        );

        if($page2 == null) { // la derniere page veux prendre une position superieur
            $app['session']->getFlashBag()->add('danger', 'Erreur lors du changement de postion');
            return $app->redirect($app['url_generator']->generate('adminPagesList'));
        }

        // on change les positons
        $page2->setPosition($oldPosition);
        $em->persist($page2);

        $page->setPosition($newPosition);
        $em->persist($page);

        $em->flush();

        $app['session']->getFlashBag()->add('info', 'Position modifiée');
        return $app->redirect($app['url_generator']->generate('adminPagesList'));
    }
}