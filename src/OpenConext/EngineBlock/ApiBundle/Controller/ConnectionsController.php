<?php

namespace OpenConext\EngineBlock\ApiBundle\Controller;

use EngineBlock_ApplicationSingleton;
use OpenConext\Component\EngineBlockMetadata\Entity\Assembler\JanusPushMetadataAssembler;
use OpenConext\Component\EngineBlockMetadata\MetadataRepository\DoctrineMetadataRepository;
use OpenConext\EngineBlock\ApiBundle\Http\Exception\ApiAccessDeniedHttpException;
use OpenConext\EngineBlock\ApiBundle\Http\Exception\BadApiRequestHttpException;
use OpenConext\EngineBlock\ApiBundle\Http\Request\JsonRequestHelper;
use OpenConext\EngineBlock\ApiBundle\Service\FeaturesService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ConnectionsController
{
    /**
     * @var EngineBlock_ApplicationSingleton
     */
    private $engineBlockApplicationSingleton;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var FeaturesService
     */
    private $featuresService;

    /**
     * @param EngineBlock_ApplicationSingleton $engineBlockApplicationSingleton
     * @param AuthorizationCheckerInterface    $authorizationChecker
     * @param FeaturesService                  $featuresService
     */
    public function __construct(
        EngineBlock_ApplicationSingleton $engineBlockApplicationSingleton,
        AuthorizationCheckerInterface $authorizationChecker,
        FeaturesService $featuresService
    ) {
        $this->engineBlockApplicationSingleton = $engineBlockApplicationSingleton;
        $this->authorizationChecker = $authorizationChecker;
        $this->featuresService = $featuresService;
    }

    public function pushConnectionsAction(Request $request)
    {
        if (!$this->featuresService->metadataPushIsEnabled()) {
            return new JsonResponse(null, 404);
        }

        if (!$this->authorizationChecker->isGranted(array('ROLE_API_USER_JANUS'))) {
            throw new ApiAccessDeniedHttpException();
        }

        ini_set('memory_limit', '265M');

        $body = JsonRequestHelper::decodeContentOf($request);

        if (!is_object($body) || !isset($body->connections) && !is_object($body->connections)) {
            throw new BadApiRequestHttpException('Unrecognized structure for JSON');
        }

        $assembler = new JanusPushMetadataAssembler();
        $roles     = $assembler->assemble($body->connections);

        $diContainer = $this->engineBlockApplicationSingleton->getDiContainer();
        $doctrineRepository = DoctrineMetadataRepository::createFromConfig(array(), $diContainer);

        $result = $doctrineRepository->synchronize($roles);

        return new JsonResponse($result);
    }
}