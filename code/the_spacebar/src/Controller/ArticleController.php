<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticleController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function homepage()
    {
        return new Response('OMG! My first page already! WOOO!');
    }

    /**
     * @Route("/news/{slug}")
     */
    public function news($slug)
    {
        $oM = new \nabucco\Morpheus('zlink');
        $oT = $oM->select(0);

        $comments = [
            'I ate a normal rock once. It did NOT taste like bacon!',
            'Woohoo! I\'m going on an all-asteroid diet!',
            'I like bacon too! Buy some from my site! bakinsomebacon.com',
        ];

        return $this->render(
            'article/show.html.twig',
            [
                'title' => ucwords(str_replace('-', ' ', $slug)),
                'comments' => $comments,
                'test'  => $oT->source()
            ]

        );
    }
}
