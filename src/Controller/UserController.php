<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use App\Service\SESEmailClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class UserController extends Controller
{
    /**
     * @var SESEmailClient
     */
    private $ses;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(SESEmailClient $ses, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->ses = $ses;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/user", name="user")
     */
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    /**
     * @Route("/register", name="register")
     * Register new user
     */
    public function register(
        UserPasswordHasherInterface $userPasswordHasher,
        LoginFormAuthenticator $authenticator,
        UserAuthenticatorInterface $userAuthenticator,
        Request $request)
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setCreated(new \DateTime('now'));
            $rand = $this->randomString();
            $user->setVerification($rand);
            $plain = $form['plainPassword']->getData();
            $user->setPassword($userPasswordHasher->hashPassword($plain));
            $this->em->persist($user);
            $this->em->flush();

            $this->sendEmail('email.html.twig', $user, $rand, 'Welcome to Amply');

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('user/registers.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // /**
    //  * @Route("/register", name="register")
    //  **/
    // public function register(
    //     UserPasswordEncoderInterface $passwordEncoder,
    //     Request $request,
    //     GuardAuthenticatorHandler $guardHandler,
    //     LoginFormAuthenticator $formAuthenticator)
    // {
    //     if ($this->getUser()) {
    //         return $this->redirectToRoute('admin');
    //     }
    // 	if (isset($_POST['submit'])) {
    //         $em = $this->em->getManager();
    //         $user = new Users();
    //         $user->setEmail($_POST['email']);
    //         $user->setCompany($_POST['company']);
    //         $user->setTelephone($_POST['telephone']);
    //         $now = new \DateTime('now');
    //         $user->setCreated($now);
    //         $user->setEnabled(false);
    //         $rand = $this->randomString();
    //         $user->setVerification($rand);
    //         $user->setPassword($passwordEncoder->encodePassword(
    //             $user,
    //             $_POST['password']
    //         ));
    //
    //         $em->persist($user);
    //         $em->flush();
    //
    //         $this->sendEmail('email.html.twig', $user, $rand, 'Welcome to Amply');
    //
    //         return $guardHandler->authenticateUserAndHandleSuccess(
    //             $user,
    //             $request,
    //             $formAuthenticator,
    //             'main'
    //         );
    //         // return $this->render('Users/confirmation.html.twig', ['email' => $_POST['email']]);
    //     }
    //     return $this->render('user/register.html.twig', array('title' => 'Register User'));
    // }

    /**
     * @Route("/admin/account", name="account")
     **/
    public function updateProfile()
    {
        $user = $this->getUser();
        if (isset($_POST['company'])) {
            $user->setCompany($_POST['company']);
            $user->setTelephone($_POST['telephone']);
            $user->setAddress($_POST['address']);
            $user->setState($_POST['state']);
            $now = new \DateTime('now');
            $user->setModified($now);
            if ($logo = $this->upload('logo', 300)) {
                $user->setLogo($logo);
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash(
                'success',
                'Account updated'
            );

            return $this->redirectToRoute('account');
        }

        return $this->render('Users/profile.html.twig', ['title' => 'Account', 'user' => $user]);
    }

    /**
     * [sendEmail description].
     *
     * @param string $view    The view to render
     * @param Users  $user    The user to send the email to
     * @param string $rand    Random string
     * @param string $subject Email subject
     */
    private function sendEmail($view, $user, $rand, $subject)
    {
        $message = $this->renderView(
            "Users/$view",
            [
                'name' => $user->getCompany(),
                'token' => $rand,
                'host' => getenv('HOST_URL'),
            ]
        );
        $response = $this->ses->sendEmail($user->getEmail(), $subject, $message);

        return $response;
    }

    /**
     * @Route("/admin/users/verification/resend", name="resendveremail")
     *
     **/
    public function resendVerEmail(): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $user = $this->getUser();
        $rand = $user->getVerification();

        $sent = $this->sendEmail('email.html.twig', $user, $rand, 'Welcome to Amply');

        return $this->json(['status' => $sent ? 'success' : 'failed']);
    }

    /**
     * Generate a random string for email verification.
     *
     * @param int $length
     *
     * @return string
     **/
    private function randomString($length = 32)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @Route("/email/verify/{token}", name="verify_email")
     *
     * @return bool
     **/
    public function verifyEmail(string $token): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['verification' => $token]);

        if (!$user) {
            $this->addFlash(
                'danger',
                'Verification link invalid!!!'
            );
        } else {
            $user->setEnabled(true);
            $user->setVerification(null);
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash(
                'success',
                'Email verified successfully'
            );
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/password/reset", name="password_email")
     *
     * @param SESEmailClient $ses SES helper class
     **/
    public function resetPassword(SESEmailClient $ses): Response
    {
        $email = '';
        $error = null;
        if (isset($_POST['submit'])) {
            $email = $_POST['email'];
            $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $email]);
            if ($user) {
                $rand = $this->randomString(64);
                $user->setReset($rand);
                $this->em->persist($user);
                $this->em->flush();

                $this->sendEmail('passwordemail.html.twig', $user, $rand, 'Password Reset');

                return $this->render('Users/emailsent.html.twig', ['email' => $email]);
            }

            $error = 'Email not registered to any account';
        }

        return $this->render('Users/passwordreset.html.twig', ['email' => $email, 'error' => $error]);
    }

    /**
     * @Route("/password/reset/{token}", name="password_reset")
     *
     * @param string $token
     **/
    public function reset($token)
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['reset' => $token]);
        if ($user) {
            return $this->render('Users/passwordchange.html.twig', [
                'email' => $user->getEmail(),
                'token' => $token,
            ]);
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/password/change", name="password_change")
     *
     * @param PasswordHasherInterface $passwordEncoder
     **/
    public function passwordChange(PasswordHasherInterface $passwordEncoder): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $_POST['email']]);
        $error = '';

        if ($user && ($user->getReset() === $_POST['token']) && $_POST['password'] === $_POST['confirmpassword']) {
            $user->setReset('');
            $user->setPassword($passwordEncoder->hash($_POST['password']));
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash(
                'success',
                'Password changed successfully'
            );
        } else {
            $this->addFlash(
                'error',
                'Password could not be changed at this time. Try again later'
            );
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/login", name="app_login")
     */
    #[Route("/login", name: "app_login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $this->redirectToRoute('admin');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/ajax/login", name="ajax_login")
     **/
    public function rlogin(): Response
    {
        return $this->render('user/rlogin.html.twig');
    }

    /**
     * @Route("/logout", name="app_logout")
     **/
    public function logout()
    {
    }
}
