<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\User;
use App\Form\ItemType;
use App\Repository\ItemRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ItemController extends AbstractController
{
    #[Route('/item', name: 'app_item')]
    public function index(ItemRepository $itemRepository): Response
    {
        $items = $itemRepository->findAll();
        return $this->render('item/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('item/{id}', name: 'app_showItem', methods: ['GET'], requirements: ['id' => '\d+']) ]
    public function showItem(Item $items): Response
    {
        return $this->render('item/showItem.html.twig', [
            'items' => $items,
        ]);
    }

    
    #[Route('/item/delete', name: 'app_delete')]
    public function itemDelete(ItemRepository $itemRepository): Response
    {
        $user = $this->getUser();  

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $items = $itemRepository->findBy(['owner' => $user]);

        return $this->render('item/delete.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/item/delete/{id}', name: 'app_item_delete', methods: ['POST'])]
    public function delete(Request $request, Item $item, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($item);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_account', [], Response::HTTP_SEE_OTHER);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/item/new', name: 'app_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $item = new Item();
        $form = $this->createForm(ItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $pictureFile */
            $pictureFile = null;
            $pictureFile = $form->get('picture')->getData();
            if ($pictureFile) {
            $pictureFileName = $fileUploader->upload($pictureFile);
            $item->setPicture($pictureFileName);
        }

            $item->setOwner($this->getUser());

            $entityManager->persist($item);
            $entityManager->flush();
            $this->addFlash('success', 'ajouté avec succes');

            return $this->redirectToRoute('app_account', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('item/new.html.twig', [
            'item' => $item,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/item/show', name: 'app_item_show')]
    public function show(ItemRepository $itemRepository): Response
    {
        $user = $this->getUser();  

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $items = $itemRepository->findBy(['owner' => $user]);

        return $this->render('item/show.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/user/{id}/item', name: 'app_user_item')]
    public function userItem(User $user, ItemRepository $itemRepository): Response
    {
        $items = $itemRepository->findBy(['owner' => $user]);

        return $this->render('item/userItem.html.twig', [
            'items' => $items,
            'user' => $user,
        ]);
    }
}
