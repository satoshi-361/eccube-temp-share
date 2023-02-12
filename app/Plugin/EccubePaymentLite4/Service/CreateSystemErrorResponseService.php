<?php

namespace Plugin\EccubePaymentLite4\Service;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CreateSystemErrorResponseService
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(
        Environment $twig
    ) {
        $this->twig = $twig;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function get($title, $message): Response
    {
        logs('gmo_epsilon')->error('response error. get fraud GET.');

        $content = $this->twig->render('error.twig', [
            'error_title' => $title,
            'error_message' => $message,
        ]);

        return Response::create($content);
    }
}
