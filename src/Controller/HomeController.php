<?php


namespace App\Controller;


use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * homepage with description
     */

    public function home(ArticleRepository $articleRepository)
    {
        $description = $articleRepository->findBy(['type'=>1]);
        $button = $articleRepository->findBy(['type'=>2]);

        return $this->render('home.html.twig',[
            'description' => $description,
            'button' => $button
        ]);
    }

}