<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Entity\Item;
use App\Form\LoanType;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LoanController extends AbstractController
{
    #[Route('/loan', name: 'app_loan')]
    public function index(): Response
    {
        return $this->render('loan/index.html.twig', [
            'controller_name' => 'LoanController',
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/loan/new/{item}', name: 'app_loan_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Item $item, EntityManagerInterface $entityManager): Response
    {
        if($item->getOwner() === $this->getUser()) {
            $this->addFlash('error', "⚠️ Vous ne pouvez pas emprunter votre propre objet !");
            return $this->redirectToRoute('app_item');
        }

        $loan = new Loan();
        $loan->setItem($item);
        $loan->setUtilisateur($this->getUser());

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(LoanType::class, $loan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $start = $loan->getStart();
            $endLoan = $loan->getEndLoan();

            $repo = $entityManager->getRepository(Loan::class);

            if ($repo->isItemAlreadyLoaned($item, $start, $endLoan)) {
                $this->addFlash('error', "Cet objet est déjà emprunté à ces dates !");
                return $this->redirectToRoute('app_loan_new');
            }

            $category = $item->getCategory();
            $points = $category->getPoints();

            $owner = $item->getOwner();
            $owner->setPoints($owner->getPoints() + $points);

            $entityManager->persist($owner);

            $entityManager->persist($loan);
            $entityManager->flush();
            $this->addFlash('success', 'Validé');

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('loan/new.html.twig', [
            'loan' => $loan,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/loan/show', name: 'app_loan_show')]
    public function show(LoanRepository $loanRepository): Response
    {
        $user = $this->getUser();  

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $loans = $loanRepository->findBy(['utilisateur' => $user]);

        return $this->render('loan/show.html.twig', [
            'loans' => $loans,
        ]);
    }

    #[Route('/loan/owned', name: 'app_owned_show')]
    public function owned(LoanRepository $loanRepository): Response
    {
        $user = $this->getUser();  

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $loans = $loanRepository->findByItemOwner($user);

        return $this->render('loan/owned.html.twig', [
            'loans' => $loans,
        ]);
    }
}
