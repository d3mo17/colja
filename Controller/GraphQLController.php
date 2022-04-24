<?php

namespace DMo\Colja\Controller;

use DMo\Colja\GraphQL\ResolverManagerInterface;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaExtender;
use Siler\GraphQL as SGQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function Siler\array_get;

/**
 * Class GraphQLController
 * @package DMo\Colja\Controller
 */
class GraphQLController extends AbstractController
{
    /**
     * @var ResolverManagerInterface
     */
    private $resolverManager;
    /**
     * @var array $warnings
     */
    private $warnings = [];

    /**
     * GraphQLController constructor.
     * @param ResolverManagerInterface $resolverManager
     */
    public function __construct(ResolverManagerInterface $resolverManager)
    {
        $this->resolverManager = $resolverManager;
    }

    /**
     * @return Schema
     */
    private function getSchema()
    {
        $configPath = $this->getParameter('kernel.project_dir') . '/';

        $cparams = $this->getConfigParameter('d_mo_colja');
        $schema = SGQL\BuildSchema::build(
            Parser::parse(
                file_get_contents($configPath . $cparams['schema']),
                ['noLocation' => true]
            )
        );

        if (empty($cparams['extensions'])) {
            return $schema;
        }

        $filenames = array_column($cparams['extensions'], 'schema');
        $concatSources = array_reduce(
            $filenames,
            function ($carry, $filename) use ($configPath) {
                return $carry . file_get_contents($configPath . $filename) . "\n";
            },
            ''
        );

        return SchemaExtender::extend($schema, Parser::parse($concatSources, ['noLocation' => true]));
    }

    /**
     * @param string $message
     * @param int $code
     * @param array $data
     */
    public function addWarning(string $message, int $code = 0, array $data = [])
    {
        $warning = ['message' => $message];
        $code && $warning['code'] = $code;
        !empty($data) && $warning['additional_data'] = $data;

        $this->warnings[] = $warning;
    }

    /**
     * Gets a container parameter by its name.
     *
     * @return mixed
     */
    public function getConfigParameter(string $name)
    {
        return $this->getParameter($name);
    }

    /**
     * Throws an exception unless the attribute is granted against the current authentication
     * token and optionally supplied subject.
     * 
     * @param array|string $attributes - Pass user roles with granted permissions, eg.: IS_AUTHENTICATED_FULLY
     * @param mixed $subject
     * @param string $message
     */
    public function denyAccessExcept($attribute, $subject = null, string $message = 'Access Denied.'): void
    {
        $this->denyAccessUnlessGranted($attribute, $subject, $message);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function endpoint(Request $request)
    {
        $this->resolverManager
            ->setController($this)
            ->setContainer($this->container);

        SGQL\resolvers($this->resolverManager->getResolvers());
        if ($request->isMethod('get')) {
            $input = $request->query->all();
        } elseif ($request->headers->contains('content-type', 'application/graphql')) {
            $input = [];
            parse_str($request->getContent(), $input);
        } elseif ($request->request->has('query')) {
            $input = $request->request->all();
        } else {
            $input = json_decode($request->getContent(), true);
        }

        $query     = array_get($input, 'query');
        $rootValue = null;
        $qlContext = null;
        $variables = array_get($input, 'variables');
        $operation = array_get($input, 'operationName');
        $debug     = DebugFlag::RETHROW_UNSAFE_EXCEPTIONS | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;

        try {
            $result = GraphQL::executeQuery(
                $this->getSchema(),
                $query,
                $rootValue,
                $qlContext,
                $variables,
                $operation
            )->toArray($debug);

            !empty($this->warnings) && $result['extensions'] = ['warnings' => $this->warnings];
            $this->warnings = [];

            return new JsonResponse($result);
        } catch(\Exception $e) {
            $code = $e->getCode();
            $error = ['message' => $e->getMessage(), 'code' => $code];
            $debug = $_SERVER['APP_DEBUG'];
            if (!empty($debug)) {
                $error['file'] = $e->getFile();
                $error['line'] = $e->getLine();
                $error['trace'] = $e->getTraceAsString();
            }
            return new JsonResponse(
                ['errors' => [$error]],
                $code < 1000 && $code > 99 ? $code : 500
            );
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function options(Request $request)
    {
        $this->getParameter('d_mo_colja.option_request_user_restricted')
            && $this->denyAccessExcept('IS_AUTHENTICATED_FULLY');

        $content = $request->getContent();
        if (strpos($content, 'query') !== false) {
            $data   = json_decode($content, true);
            $result = SGQL\execute($this->getSchema(), $data);

            return new JsonResponse($result);
        }
    }
}
