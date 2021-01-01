<?php


namespace App\Controller;


use App\Entity\Article;
use App\Entity\Picture;
use App\Form\EcoloType;
use App\Form\HomeType;
use App\Form\PicturesType;
use App\Form\ProfileType;
use App\Form\ProjectType;
use App\Repository\ArticleRepository;
use App\Repository\PictureRepository;
use App\Repository\ProjectCategoryRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
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
    public function adminGalleryOneProject(Request $request, ArticleRepository $articleRepository, PictureRepository
    $pictureRepository)
    {
        $id = $request->query->get('id');
        $project = $articleRepository->find($id);
        $pictures = $pictureRepository->findAllPictures($id);

        return $this->render('admin/admin-gallery-one-project.html.twig',[
            'project' => $project,
            'pictures' => $pictures
        ]);
    }

    /**
     * @Route("/admin/gallery/new", name="admin_gallery_new")
     * admin gallery page = create a new project
     */
    public function adminGalleryCreate(TypeRepository $typeRepository, EntityManagerInterface $entityManager,
                                       ProjectCategoryRepository $projectCategoryRepository)
    {
        $project = new Article();
        $project->setUser($this->getUser());

        //get type 3 instance
        $type = $typeRepository->find(3);

        //get category 1 (default)
        $category = $projectCategoryRepository->find(1);

        $project->setTitle('NOUVEAU PROJET');
        $project->setType($type);
        $project->setProjectCategory($category);

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
                /** @var UploadedFile $picture */
                $picture = $form['main_picture']->getData();

                // this condition is needed because the 'brochure' field is not required
                // so the picture file must be processed only when a file is uploaded
                if ($picture) {
                    $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$picture->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        if(!is_null($project->getMainPicture())){
                            unlink("assets/img//".$project->getMainPicture());
                        }
                        $try_move = $picture->move(
                                        $this->getParameter('article_images'),
                                        $newFilename
                                    );
                        if(!$try_move){
                            throw new Exception();
                        }
                    } catch (Exception $e) {
                        // ... handle exception if something happens during file upload
                        $this->addFlash('info','Erreur lors du chargement de l\'image');
                        return $this->redirectToRoute('admin_gallery_update',[
                            'id' => $id
                        ]);
                    }

                    // updates the 'picture name' property to store the file name
                    // instead of its contents
                    $project->setMainPicture($newFilename);
                }



                $entityManager->persist($project);
                $entityManager->flush();
                $this->addFlash('info','Modification enregistrée');
                return $this->redirectToRoute('admin_gallery_update',[
                    'id' => $id
                ]);
            }

        }

        return $this->render('admin/admin-gallery-update.html.twig',[
            'form' => $formView,
            'project' => $project
        ]);
    }

    /**
     * @Route("/admin/gallery/update/pictures", name="admin_pictures_update")
     * Show all gallery project's pictures + add + remove
     */
    public function adminPicturesUpdate(Request $request, ArticleRepository $articleRepository,
                                        EntityManagerInterface $entityManager)
    {
        $new_picture = new Picture();

        //get project's ID
        $id = $request->query->get('id');

        //get project
        $project = $articleRepository->findPicturesProject($id);

        //get form
        $form = $this->createForm(PicturesType::class, $new_picture);
        $formView = $form->createView();

        if($request->isMethod('POST')){
            $form->handleRequest($request);

            if(!$form->isValid()){
                $this->addFlash('info',"Erreur lors du chargement (format : jpg, jpeg, png / taille max : 3 Mo)");
                return $this->redirectToRoute('admin_pictures_update',[
                    'id' => $id
                ]);
            }

            if($form->isSubmitted() && $form->isValid()){
                /** @var UploadedFile $picture */
                $picture = $form['name']->getData();

                // this condition is needed because the 'brochure' field is not required
                // so the picture file must be processed only when a file is uploaded
                if ($picture) {
                    $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $picture->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $try_move = $picture->move(
                            $this->getParameter('article_images'),
                            $newFilename
                        );
                        if (!$try_move) {
                            throw new Exception();
                        }
                    } catch (Exception $e) {
                        // ... handle exception if something happens during file upload
                        $this->addFlash('info', 'Erreur lors du chargement de l\'image');
                        return $this->redirectToRoute('admin_pictures_update', [
                            'id' => $id
                        ]);
                    }

                    // updates the 'picture name' property to store the file name
                    // instead of its contents
                    $new_picture->setName($newFilename);
                    $new_picture->setArticle($project);
                }
            }

            $entityManager->persist($new_picture);
            $entityManager->flush();
            return $this->redirectToRoute('admin_pictures_update',[
                'id' => $id
            ]);

        }

        return $this->render('admin/admin-pictures.html.twig',[
            'project' => $project,
            'form' => $formView
        ]);

    }

    /**
     * @Route("/admin/gallery/remove/picture/{id}", name="admin_remove_picture")
     * Back to remove picture / no view
     */
    public function removePicture($id, EntityManagerInterface $entityManager, PictureRepository $pictureRepository)
    {
        // get picture
        $picture = $pictureRepository->find($id);

        //get project's ID
        $id_project = $picture->getArticle()->getId();

        $entityManager->remove($picture);
        $entityManager->flush();

        //remove the picture in directory
        unlink("assets/img//".$picture->getName());

        return $this->redirectToRoute('admin_pictures_update',[
            'id' => $id_project
        ]);

    }

    /**
     * @Route("/admin/gallery/remove", name="admin_gallery_remove")
     * admin gallery page = remove a project
     */
    public function adminGalleryRemove(Request $request, ArticleRepository $articleRepository, EntityManagerInterface
$entityManager, PictureRepository $pictureRepository)
    {
        $id = $request->query->get('id');

        $project = $articleRepository->find($id);
        $pictures = $pictureRepository->findBy(['article' => $project]);

        foreach ($pictures as $picture){
            $entityManager->remove($picture);
            //remove the picture in directory
            unlink("assets/img//".$picture->getName());
        }

        $entityManager->remove($project);
        $entityManager->flush();
        return $this->redirectToRoute('admin_gallery');
    }

    /**
     * @Route("/admin/ecolo", name="admin_ecolo")
     * admin ecolo's page
     */
    public function adminEcolo(ArticleRepository $articleRepository, Request $request, EntityManagerInterface $entityManager)
    {
        //get ecolo's description
        $description = $articleRepository->findBy(['type' => 5]);
        $description = $description[0];

        //get ecolo's articles
        $articles = $articleRepository->findBy(['type' => 6]);

        $form = $this->createForm(HomeType::class, $description);
        $formView = $form->createView();

        if($request->isMethod('POST') && isset($_POST['submit-description'])){
            $form->handleRequest($request);

            if($form->isValid()){
                $entityManager->persist($description);
                $entityManager->flush();
                return $this->redirectToRoute('admin_ecolo');
            }
        }

        return $this->render('admin/admin-ecolo.html.twig',[
            'form' => $formView,
            'articles' => $articles
        ]);

    }

    /**
     * @Route("/admin/ecolo/article", name="admin_ecolo_article")
     * admin ecolo article page to update it
     */
    public function adminEcoloArticle(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager)
    {
        //get article's ID
        $id = $request->query->get('id');
        //get article
        $article = $articleRepository->find($id);

        //make form
        $form = $this->createForm(EcoloType::class, $article);

        //if form is submit
        if($request->isMethod('POST')){
            $form->handleRequest($request);

            if($form->isValid() && $form->isSubmitted()){

                /** @var UploadedFile $picture */
                $picture = $form['main_picture']->getData();

                // this condition is needed because the 'brochure' field is not required
                // so the picture file must be processed only when a file is uploaded
                if ($picture) {
                    $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $picture->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $try_move = $picture->move(
                            $this->getParameter('article_images'),
                            $newFilename
                        );
                        if (!$try_move) {
                            throw new Exception();
                        }

                        if(!is_null($article->getMainPicture())){
                            unlink("assets/img//".$article->getMainPicture());
                        }
                    } catch (Exception $e) {
                        // ... handle exception if something happens during file upload
                        $this->addFlash('info', 'Erreur lors du chargement de l\'image');
                        return $this->redirectToRoute('admin_ecolo_article', [
                            'id' => $id
                        ]);
                    }

                    // updates the 'picture name' property to store the file name
                    // instead of its contents
                    $article->setMainPicture($newFilename);

                }

                $entityManager->persist($article);
                $entityManager->flush();
                $this->addFlash('info','Article modifié');
                return $this->redirectToRoute('admin_ecolo_article',[
                    'id' => $id
                ]);
            }else{
                $this->addFlash('info','Erreur sur le formulaire (ex : taille de l\'image)');
                return $this->redirectToRoute('admin_ecolo_article',[
                    'id' => $id
                ]);
            }
        }


        return $this->render('admin/admin-ecolo-article.html.twig',[
            'form' => $form->createView(),
            'article' => $article
        ]);

    }

    /**
     * @Route("/admin/ecolo/delete", name="admin_ecolo_delete")
     * admin ecolo page to remove article
     */
    public  function adminEcoloDelete(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager)
    {
        //get article's ID
        $id = $request->query->get('id');
        //get article
        $article = $articleRepository->find($id);

        $entityManager->remove($article);
        $entityManager->flush();
        return $this->redirectToRoute('admin_ecolo');
    }

    /**
     * @Route("/admin/ecolo/add", name="admin_ecolo_add")
     * admin ecolo page to add an article
     */
    public function adminEcoloAdd(TypeRepository $typeRepository, EntityManagerInterface $entityManager)
    {
        $article = new Article();
        //get type
        $type = $typeRepository->find(6);
        //get user
        $user = $this->getUser();

        $article->setType($type);
        $article->setUser($user);

        $entityManager->persist($article);
        $entityManager->flush();

        return $this->redirectToRoute('admin_ecolo');
    }

    /**
     * @Route("/admin/profile", name="admin_profile")
     * admin profile page
     */
    public function adminProfile(Request $request, EntityManagerInterface $entityManager)
    {
        //get current user
        $user = $this->getUser();

        //make form to update profile
        $form = $this->createForm(ProfileType::class, $user);

        //if form is submit
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                /** @var UploadedFile $picture */
                $picture = $form['picture']->getData();
                $background = $form['background']->getData();

                // this condition is needed because the 'brochure' field is not required
                // so the picture file must be processed only when a file is uploaded
                if ($picture || $background) {

                    if ($picture) {
                        $originalFilename = pathinfo($picture->getClientOriginalName(), PATHINFO_FILENAME);
                        // this is needed to safely include the file name as part of the URL
                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $picture->guessExtension();


                        // Move the file to the directory where brochures are stored
                        try {
                            $try_move = $picture->move(
                                $this->getParameter('article_images'),
                                $newFilename
                            );

                            if (!$try_move) {
                                throw new Exception();
                            }

                            if (!is_null($user->getPicture())) {
                                unlink("assets/img//" . $user->getPicture());
                            }


                        } catch (Exception $e) {
                            // ... handle exception if something happens during file upload
                            $this->addFlash('info', 'Erreur lors du chargement de l\'image');
                            return $this->redirectToRoute('admin_profile');
                        }

                        // updates the 'picture name' property to store the file name
                        // instead of its contents
                        $user->setPicture($newFilename);
                    }

                    if ($background) {

                        $newFilenameBackground = "logo-background";
                        try {
                            if (!is_null($user->getBackground())) {
                                unlink("assets/img/logo-background");
                            }
                            $try_move_background = $background->move(
                                $this->getParameter('article_images'),
                                $newFilenameBackground
                            );

                            if (!$try_move_background) {
                                throw new Exception();
                            }
                        } catch (Exception $e2) {
                            // ... handle exception if something happens during file upload
                            $this->addFlash('info', 'Erreur lors du chargement de l\'image de fond');
                            return $this->redirectToRoute('admin_profile');
                        }


                        $user->setBackground($newFilenameBackground);
                    }

                }

                    $entityManager->persist($user);
                    $entityManager->flush();
                    return $this->redirectToRoute('admin_profile');

            }else{
                $this->addFlash('info','Erreur sur le formulaire (ex : taille ou format d\'une image)');
                return $this->redirectToRoute('admin_profile');
            }
        }

        return $this->render('admin/admin-profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }




}