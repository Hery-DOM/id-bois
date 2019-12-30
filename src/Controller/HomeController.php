<?php


namespace App\Controller;


use App\Repository\ArticleRepository;
use App\Repository\ProjectCategoryRepository;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * homepage with description
     */

    public function home(ArticleRepository $articleRepository, UserManagerInterface $userManager)
    {
        $description = $articleRepository->findBy(['type'=>1]);
        $button = $articleRepository->findBy(['type'=>2]);
        $profile = $userManager->findUserBy(['id' => 1]);

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
    $articleRepository, UserManagerInterface $userManager)
    {
        $profile = $userManager->findUserBy(['id' => 1]);
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
    public function galleryCategory(ArticleRepository $articleRepository, Request $request, ProjectCategoryRepository $projectCategoryRepository, UserManagerInterface $userManager)
    {
        $profile = $userManager->findUserBy(['id' => 1]);
        $categories = $projectCategoryRepository->findAll();
        //get category's ID
        $id = $request->query->get('id');
        //get projects
        if($id != 'all'){
            $projects = $articleRepository->findBy(['project_category' => $id]);
        }else{
            $projects = $articleRepository->findBy(['type'=>3]);
        }

        return $this->render('gallery_one_category.html.twig',[
            'profile' => $profile,
            'categories' => $categories,
            'projects' => $projects
        ]);
    }

    /**
     * @Route("/project/{id}", name="project")
     * show pictures from a project, by ajax (carousel)
     */
    public function project(ArticleRepository $articleRepository,$id)
    {
        //get project
        $project = $articleRepository->findPicturesProject($id);

        return $this->render('project.html.twig',[
            'project' => $project
        ]);
    }

    /**
     * @Route("/ecolo",name="ecolo")
     * ecolo page
     */
    public function ecolo(ArticleRepository $articleRepository, UserManagerInterface $userManager)
    {
        $profile = $userManager->findUserBy(['id' => 1]);
        //get ecolo's description
        $description = $articleRepository->findBy(['type' => 5]);

        //get ecolo's articles
        $articles = $articleRepository->findBy(['type' => 6]);

        return $this->render('ecolo.html.twig',[
            'profile' => $profile,
            'description' => $description,
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     * contact page
     */
    public function contact(ArticleRepository $articleRepository, Request $request, UserManagerInterface $userManager)
    {
        $profile = $userManager->findUserBy(['id' => 1]);

        //if form is send
        if($request->isMethod('POST')){

            //check security
            $name = htmlspecialchars($_POST['name']);
            $firstname = htmlspecialchars($_POST['firstname']);
            $phone = htmlspecialchars($_POST['phone']);
            $project = htmlspecialchars($_POST['project']);

            //check if inputs are empty
            if(empty($name) || empty($firstname) || empty($phone) || empty($project)){
                $this->addFlash('confirm', 'Merci de remplir tous les champs');
                return $this->render('contact.html.twig',[
                    'profile' => $profile
                ]);
            }


            $to = 'id-bois@hotmail.fr';
            $subject = 'Message du site web';
            $message = wordwrap($project, 70,"\r\n");
            $headers = 'Du formulaire du site id-bois.fr' . "\r\n" .
                'Message de '.$firstname.' '.$name . "\r\n".
                'Téléphone : '.$phone. "\r\n";

            $mail = mail($to, $subject, $message, $headers);


            if($mail){
                $this->addFlash('confirm', 'Votre message a bien été envoyé');
                return $this->render('contact.html.twig',[
                    'profile' => $profile
                ]);
            }else{
                $this->addFlash('confirm', 'Il y a eu une erreur lors de l\'envoi de votre message, merci de réessayer');
                return $this->render('contact.html.twig',[
                    'profile' => $profile
                ]);
            }
        }

        return $this->render('contact.html.twig',[
            'profile' => $profile
        ]);
    }

    /**
     * @Route("/mentions-legales", name="legal")
     * legal notice page
     */
    public function legal(ArticleRepository $articleRepository, UserManagerInterface $userManager)
    {
        $profile = $userManager->findUserBy(['id' => 1]);

        return $this->render('legal.html.twig',[
            'profile' => $profile
        ]);
    }

}