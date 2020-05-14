<?php


namespace App\Controller;


use App\Entity\Cookie;
use App\Repository\ArticleRepository;
use App\Repository\CookieRepository;
use App\Repository\ProjectCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    private function get_ip()
    {
        if ( isset ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        elseif ( isset ( $_SERVER['HTTP_CLIENT_IP'] ) )
        {
            $ip  = $_SERVER['HTTP_CLIENT_IP'];
        }
        else
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    private function checkCookie(CookieRepository $cookieRepository)
    {
        //get user's IP
        $ip = $this->get_ip();

        //get IP in database
        $cookies = $cookieRepository->findAll();
        $ips = [];
        foreach($cookies as $cookie){
            $ips[] = $cookie->getIp();
        }
        $cookie_target = $cookieRepository->findOneBy(['ip' => $ip]);
        if($cookie_target){
            $cookie_target_date = $cookie_target->getDate();
        }else{
            return true;
        }

        //get date
        $date = new \DateTime();
        $tomorrow = time($cookie_target_date)+10;

        //dump($cookie_target_date);
        //dump(date($cookie_target_date->format('Y-m-d'),mktime(0,0,0,0,0,0)));
        $mydate = $cookie_target_date->diff(new \DateTime());

        if(in_array($ip, $ips) && $mydate->days < 2){
            return false;
        }else{
            return true;
        }

    }

    /**
     * @Route("/", name="home")
     * homepage with description
     */

    public function home(ArticleRepository $articleRepository, UserManagerInterface $userManager, CookieRepository $cookieRepository)
    {
        $description = $articleRepository->findBy(['type'=>1]);
        $button = $articleRepository->findBy(['type'=>2]);
        $profile = $userManager->findUserBy(['id' => 1]);

        return $this->render('home.html.twig',[
            'description' => $description,
            'button' => $button,
            'profile' => $profile,
            'cookie' => $this->checkCookie($cookieRepository)
        ]);
    }

    /**
     * @Route("/galerie", name="gallery_home")
     * gallery page, to select the category
     */
    public function galleryHome(ProjectCategoryRepository $projectCategoryRepository, ArticleRepository
    $articleRepository, UserManagerInterface $userManager, CookieRepository $cookieRepository)
    {
        $profile = $userManager->findUserBy(['id' => 1]);
        $categories = $projectCategoryRepository->findAll();

        return $this->render('gallery_categories.html.twig',[
            'categories' => $categories,
            'profile' => $profile,
            'cookie' => $this->checkCookie($cookieRepository)
        ]);
    }

    /**
     * @Route("/galerie/categorie",name="gallery_category")
     * gallery page, show products from category selected
     */
    public function galleryCategory(ArticleRepository $articleRepository, Request $request, ProjectCategoryRepository $projectCategoryRepository, UserManagerInterface $userManager, CookieRepository $cookieRepository)
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
            'projects' => $projects,
            'cookie' => $this->checkCookie($cookieRepository)
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
    public function ecolo(ArticleRepository $articleRepository, UserManagerInterface $userManager, CookieRepository $cookieRepository)
    {
        $profile = $userManager->findUserBy(['id' => 1]);
        //get ecolo's description
        $description = $articleRepository->findBy(['type' => 5]);

        //get ecolo's articles
        $articles = $articleRepository->findBy(['type' => 6]);

        return $this->render('ecolo.html.twig',[
            'profile' => $profile,
            'description' => $description,
            'articles' => $articles,
            'cookie' => $this->checkCookie($cookieRepository)
        ]);
    }

    /**
     * @Route("/contact", name="contact")
     * contact page
     */
    public function contact(ArticleRepository $articleRepository, Request $request, UserManagerInterface $userManager, CookieRepository $cookieRepository)
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
                    'profile' => $profile,
                    'cookie' => $this->checkCookie($cookieRepository)
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
                    'profile' => $profile,
                    'cookie' => $this->checkCookie($cookieRepository)

                ]);
            }else{
                $this->addFlash('confirm', 'Il y a eu une erreur lors de l\'envoi de votre message, merci de réessayer');
                return $this->render('contact.html.twig',[
                    'profile' => $profile,
                    'cookie' => $this->checkCookie($cookieRepository)
                ]);
            }
        }

        return $this->render('contact.html.twig',[
            'profile' => $profile,
            'cookie' => $this->checkCookie($cookieRepository)
        ]);
    }

    /**
     * @Route("/mentions-legales", name="legal")
     * legal notice page
     */
    public function legal(ArticleRepository $articleRepository, UserManagerInterface $userManager, CookieRepository $cookieRepository)
    {
        $profile = $userManager->findUserBy(['id' => 1]);

        return $this->render('legal.html.twig',[
            'profile' => $profile,
            'cookie' => $this->checkCookie($cookieRepository)
        ]);
    }

    /**
     * @Route("/cookie", name="cookie")
     * No view, just to save IP for accepting cookie
     */
    public function cookie(CookieRepository $cookieRepository, EntityManagerInterface $entityManager)
    {

        //get user's IP
        $ip = $this->get_ip();

        //to check if user's IP isn't already unregistred
        $ip_bdd = $cookieRepository->findOneBy(['ip' => $ip]);

        if(empty($ip_bdd)){
            $cookie = new Cookie();
            $cookie->setDate(new \DateTime());
            $cookie->setIp($ip);
            $entityManager->persist($cookie);
            $entityManager->flush();
        }else{
            $cookie = $ip_bdd;
            $cookie->setDate(new \DateTime());
            $cookie->setIp($ip);
            $entityManager->persist($cookie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('home');

    }

}