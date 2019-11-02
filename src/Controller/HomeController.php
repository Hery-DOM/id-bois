<?php


namespace App\Controller;


use App\Repository\ArticleRepository;
use App\Repository\ProjectCategoryRepository;
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
        $profile = $articleRepository->findBy(['type'=>7]);

        return $this->render('home.html.twig',[
            'description' => $description,
            'button' => $button,
            'profile' => $profile
        ]);
    }

    /**
     * @Route("/galerie", name="gallery_home")
     * gallery page, to select the category
     */
    public function galeryHome(ProjectCategoryRepository $projectCategoryRepository, ArticleRepository $articleRepository)
    {
        $profile = $articleRepository->findBy(['type'=>7]);
        $categories = $projectCategoryRepository->findAll();

        return $this->render('gallery_categories.html.twig',[
            'categories' => $categories,
            'profile' => $profile
        ]);
    }

}