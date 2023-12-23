<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Article;
use App\Repository\UserRepository;
use App\Service\ArticleServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use http\Env\Response;
use http\Exception\BadConversionException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api',name: 'api')]
class ApiController extends AbstractController
{

    private const DEFAULT_COUNT = 30;

    #[Route('/recent-articles', methods:['GET'])]
    public function getArticles(Request $request, ArticleServiceInterface $articleService, int $count = self::DEFAULT_COUNT): JsonResponse
    {
        if (!($request->query->has('count') && ctype_digit($request->query->has('count')))){
            $count = self::DEFAULT_COUNT;
        }

        $data = [];

        /** @var  Article $article */
        foreach ($articleService->getRecentArticles($count)->getQuery()->getResult() as $article){
            $data = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'body' => $article->getBody(),
                'created_at' =>$article->getCreateAt()->format('d.m.Y. H:i:s'),
                'author' => [
                    'id' => $article->getAuthor()->getId(),
                    'name' => $article->getAuthor()->__toString(),
                ],
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/article/{article}', methods:['GET'])]
    public function article(Article $article):JsonResponse
    {
        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'body' => $article->getBody(),
            'created_at' =>$article->getCreateAt()->format('d.m.Y. H:i:s'),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'name' => $article->getAuthor()->__toString(),
            ],
        ]);
    }

    #[Route('article', methods:['POST'])]
    public function createArticle(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        $errorTemplate = 'Поле %s не может быть пустым';
        $errors = [];
        if (!$request->request->has('title')){
            $errors[] = sprintf($errorTemplate, 'title');
        }

        if (!$request->request->has('body')){
            $errors[] = sprintf($errorTemplate, 'body');
        }

        if (!empty($errors)){
            return new JsonResponse($errors, \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }

        $article = new Article();

        $article->setTitle($request->request->get('title'))
            ->setBody($request->request->get('body'))
            ->setAuthor($userRepository->findOneBy([]));

        $entityManager->persist($article);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'body' => $article->getBody(),
            'created_at' =>$article->getCreateAt()->format('d.m.Y. H:i:s'),
            'author' => [
                'id' => $article->getAuthor()->getId(),
                'name' => $article->getAuthor()->__toString(),
            ],
        ], 201);
    }
}