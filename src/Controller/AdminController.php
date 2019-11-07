<?php


namespace App\Controller;


use App\Entity\Article;
use App\Form\HomeType;
use App\Form\ProjectType;
use App\Repository\ArticleRepository;
use App\Repository\TypeRepository;
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
                $this->addFlash('info', "Description modifiée");
                $this->redirectToRoute('admin_home');
            }
        }

        //if formCall is submit
        if($request->isMethod('POST') && isset($_POST['call'])){
            $formCall->handleRequest($request);

            if($formCall->isValid() && $formCall->isSubmitted()){
                $entityManager->persist($call);
                $entityManager->flush();
                $this->addFlash('info', "Bouton \"call-to-action\" modifié");
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

    /**
     * @Route("/admin/gallery/project",name="admin_gallery_single_project")
     * admin gallery page = show one project
     */
    public function adminGalleryOneProject(Request $request, ArticleRepository $articleRepository)
    {
        $id = $request->query->get('id');
        $project = $articleRepository->find($id);

        return $this->render('admin/admin-gallery-one-project.html.twig',[
            'project' => $project
        ]);
    }

    /**
     * @Route("/admin/gallery/new", name="admin_gallery_new")
     * admin gallery page = create a new project
     */
    public function adminGalleryCreate(TypeRepository $typeRepository, EntityManagerInterface $entityManager)
    {
        $project = new Article();
        $project->setUser($this->getUser());

        //get type 3 instance
        $type = $typeRepository->find(3);

        $project->setTitle('NOUVEAU PROJET');
        $project->setType($type);

        $entityManager->persist($project);
        $entityManager->flush();

        return $this->redirectToRoute('admin_gallery');
    }

    /**
     * @Route("/admin/gallery/update", name="admin_gallery_update")
     * admin gallery page = update a project
     */
    public function adminGalleryUpdate(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager)
    {
        $id = $request->query->get('id');
        $project = $articleRepository->find($id);

        $form = $this->createForm(ProjectType::class, $project);
        $formView = $form->createView();

        //if form is submit
        if($request->isMethod('POST')){
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                $entityManager->persist($project);
                $entityManager->flush();
                $this->addFlash('info','Modification enregistrée');
                return $this->redirectToRoute('admin_gallery_update',[
                    'id' => $id
                ]);
            }

        }

        return $this->render('admin/admin-gallery-update.html.twig',[
            'form' => $formView
        ]);
    }

    /**
     * @Route("/admin/gallery/remove", name="admin_gallery_remove")
     * admin gallery page = remove a project
     */
    public function adminGalleryRemove(Request $request, ArticleRepository $articleRepository, EntityManagerInterface
$entityManager)
    {
        $id = $request->query->get('id');
        $project = $articleRepository->find($id);
        $entityManager->remove($project);
        $entityManager->flush();
        return $this->redirectToRoute('admin_gallery');
    }


}