<?php


namespace App\Controller;


use App\Form\HomeType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin/home",name="admin_home")
     * admin home page
     */
    public function adminHome(ArticleRepository $articleRepository, Request $request, EntityManagerInterface $entityManager)
    {
        //make a form for home's page description
        $description = $articleRepository->findBy(['type' => 1]);
        $description = $description[0];
        $formDescription = $this->createForm(HomeType::class, $description);

        //make a form for home's page call-to-action
        $call = $articleRepository->findBy(['type' => 2]);
        $call = $call[0];
        $formCall = $this->createForm(HomeType::class, $call);

        //if a formDescription is submit
        if($request->isMethod('POST') && isset($_POST['description'])){

           $formDescription->handleRequest($request);


            if($formDescription->isValid() && $formDescription->isSubmitted()){
                $entityManager->persist($description);
                $entityManager->flush();
                $this->redirectToRoute('admin_home');
            }
        }

        //if formCall is submit
        if($request->isMethod('POST') && isset($_POST['call'])){
            $formCall->handleRequest($request);

            if($formCall->isValid() && $formCall->isSubmitted()){
                $entityManager->persist($call);
                $entityManager->flush();
                $this->redirectToRoute('admin_home');
            }
        }

        return $this->render('admin/admin-home.html.twig',[
            'formDescription' => $formDescription->createView(),
            'formCall' => $formCall->createView()
        ]);
    }

    /**
     * @Route("/admin/gallery",name="admin_gallery")
     * admin gallery page = show all projects
     */
    public function adminGallery(ArticleRepository $articleRepository)
    {
        $projects = $articleRepository->findAllProjects();

        return $this->render('admin/admin-gallery.html.twig',[
            'projects' => $projects
         ]);
    }


}