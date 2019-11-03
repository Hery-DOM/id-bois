<?php


namespace App\Controller;


use App\Repository\ArticleRepository;
use App\Repository\ProjectCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function galleryHome(ProjectCategoryRepository $projectCategoryRepository, ArticleRepository
    $articleRepository)
    {
        $profile = $articleRepository->findBy(['type'=>7]);
        $categories = $projectCategoryRepository->findAll();

        return $this->render('gallery_categories.html.twig',[
            'categories' => $categories,
            'profile' => $profile
        ]);
    }

    /**
     * @Route("/galerie/categorie",name="gallery_category")
     * gallery page, show products from category selected
     */
    public function galleryCategory(ArticleRepository $articleRepository, Request $request, ProjectCategoryRepository $projectCategoryRepository)
    {
        $profile = $articleRepository->findBy(['type'=>7]);
        $categories = $projectCategoryRepository->findAll();
        //get category's ID
        $id = $request->query->get('id');
        //get projects
        $projects = $articleRepository->findBy(['project_category' => $id]);

        return $this->render('gallery_one_category.html.twig',[
            'profile' => $profile,
            'categories' => $categories,
            'projects' => $projects
        ]);
    }

}