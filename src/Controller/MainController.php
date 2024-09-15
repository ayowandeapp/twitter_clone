<?php

namespace App\Controller;

use App\DTO\Response\PostResponse;
use App\Entity\Post;
use App\Service\FailedValidationException;
use App\Service\ServiceException;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\PostDTO;
use App\Service\Post\PostService;
use App\Service\ProcessExceptionData;
use App\Service\Serializer\DTOSerializer;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MainController extends AbstractController
{
    public function __construct(private PostService $postService, private ValidatorInterface $validator) {}

    #[Route(path: "/posts", name: "app_posts", methods: [Request::METHOD_GET])]
    public function index(Request $request): Response
    {
        $posts = $this->postService->getPost($request);

        return new JsonResponse($posts, Response::HTTP_OK);
    }

    #[Route(path: "/post/{id}", name: "app_post", methods: [Request::METHOD_GET])]
    public function show(int $id): Response
    {
        $post = $this->postService->findPostById($id);

        return new JsonResponse((new PostResponse($post))->toArray(), Response::HTTP_OK);
    }

    #[Route(path: "/delete/post/{id}", name: "app_post", methods: [Request::METHOD_DELETE])]
    public function destroy(int $id): Response
    {
        $post = $this->postService->deletePost($id);

        return new JsonResponse($post, Response::HTTP_OK);
    }

    #[Route('/save/post', name: 'app_save_post', methods: [Request::METHOD_POST])]
    public function savePost(Request $request): JsonResponse
    {
        // Deserialize JSON request content into PostDTO
        $postDTO = $this->postValidation($request->getContent(), 'create');

        $post = $this->postService->savePost($postDTO);

        return new JsonResponse((new PostResponse($post))->toArray(), 201);
    }

    #[Route(path: '/edit/{id}/post', name: 'app_edit_post', methods: [Request::METHOD_PUT])]
    public function editPost(Request $request, int $id)
    {
        // Deserialize JSON request content into PostDTO
        $postDTO = $this->postValidation($request->getContent());

        $post = $this->postService->editPost($id, $postDTO);

        return new JsonResponse((new PostResponse($post))->toArray(), 201);
    }

    ///put this in a class 
    private function postValidation($data, string $type = null): PostDTO
    {
        /**
         * @var DTOSerializer 
         */
        $serializer = new DTOSerializer;

        // Deserialize JSON request content into PostDTO
        $postDTO = $serializer->deserialize($data, PostDTO::class, 'json');

        //validation        
        $errors = $this->validator->validate($postDTO, groups: $type ? [$type] : '');

        //if validation failed 
        if (count($errors) > 0) {
            //process validation data
            $exceptionData = new ProcessExceptionData($errors, Response::HTTP_BAD_REQUEST);
            throw new FailedValidationException($exceptionData);
        }

        return $postDTO;
    }
}