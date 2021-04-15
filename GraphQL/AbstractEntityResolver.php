<?php

namespace DMo\Colja\GraphQL;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectRepository;

/**
 * This class is meant to be a helper to work with doctrine 2 entities
 * @package DMo\Colja\GraphQL
 */
abstract class AbstractEntityResolver extends AbstractResolver
{
    /**
     * This method should render the response of the resolved entity-request for graphql.
     * If necessary overwrite this method in subclasses to your needs (i. e. to exclude
     * fields from entity which should not be available in the graphql result).
     * @param $entity
     * @return array
     */
    protected function structure($entity): array
    {
        return $this->convertEntityToArray($entity);
    }

    /**
     * This method should return the classname of the managed entity-type.
     * @return string
     */
    abstract protected function getEntityClassname(): string;

    /**
     * @return array
     */
    protected function getDefaultOrder(): array
    {
        return ['id' => 'ASC'];
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine(): ManagerRegistry
    {
        return $this->getResolverManager()->getDoctrine();
    }

    /**
     * @param string|null $className
     * @return ObjectRepository
     */
    protected function getRepository(string $className = null): ObjectRepository
    {
        return $this->getDoctrine()->getRepository($className ?: $this->getEntityClassname());
    }

    /**
     * @param array $orderData
     * @return array
     */
    protected function convertOrder(array $orderData): array
    {
        $conversion = [];
        foreach ($orderData as $sort) {
            $conversion[$sort['field']] = $sort['direction'];
        }
        return $conversion;
    }

    /**
     * @param array|null $root
     * @param array $args
     * @return object
     * @throws EntityNotFoundException
     */
    protected function getEntity(array $root = null, array $args): object
    {
        $entityRepo = $this->getRepository();
        $entity = $entityRepo->find($args['id']);

        if (empty($entity)) {
            throw new EntityNotFoundException('No entity (id ' . $args['id'] . ') found!', 404);
        }

        return $entity;
    }

    /**
     * @param array|null $root
     * @param array $args
     * @return array
     */
    protected function getAllEntities(array $root = null, array $args): array
    {
        $repository = $this->getRepository();

        $order = \array_key_exists('order', $args)
            ? $this->convertOrder($args['order'])
            : $this->getDefaultOrder();
        $limit = \array_key_exists('limit', $args) ? $args['limit'] : null;
        $offset = \array_key_exists('offset', $args) ? $args['offset'] : null;
        $filter = \array_key_exists('filter', $args) ? $args['filter'] : [];
        $entities = $repository->findby($filter, $order, $limit, $offset);

        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->structure($entity);
        }

        return $result;
    }

    /**
     * @param array|null $root
     * @param array $args
     * @return bool
     * @throws EntityNotFoundException
     */
    protected function deleteEntity(array $root = null, array $args): bool
    {
        $doctrine = $this->getDoctrine();
        $repository = $doctrine->getRepository($this->getEntityClassname());
        $entity = $repository->find($args['id']);

        if (empty($entity)) {
            throw new EntityNotFoundException(
                'No deletion, cause no entity (id ' . $args['id'] . ') was found!',
                404
            );
        }

        try {
            $em = $doctrine->getManager();
            $em->remove($entity);
            $em->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Use this method as helper to structure an entity as array
     * @param $entity
     * @param array|null $fieldnamesToSkip Should only contain the name of fields
     *                                     which should be exlude from structure
     * @param array|null $fieldnameToDateFormat [
     *                      key: The fieldname carrying a date object,
     *                      value: The string format the date should appear
     *                   ]
     * @return array
     * @throws \Exception
     */
    protected function convertEntityToArray(
        $entity,
        ?array $fieldnamesToSkip = [],
        ?array $fieldnameToDateFormat = []
    ): array {

        if (!$entity) {
            throw new \Exception("Entity missing!", 1609880104);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $classMeta = $entityManager->getClassMetadata(get_class($entity));
        $structure = [];
        $this->enrichStructureByEntityAssociations(
            $structure,
            $classMeta,
            $entity,
            $fieldnamesToSkip
        );
        $this->enrichStructureByEntityFields(
            $structure,
            $classMeta,
            $entity,
            $fieldnamesToSkip,
            $fieldnameToDateFormat
        );

        return $structure;
    }

    /**
     * @param array $structure
     * @param ClassMetadata $classMeta
     * @param $entity
     * @param array $fieldnamesToSkip
     * @param array|null $fieldnameToDateFormat
     */
    private function enrichStructureByEntityFields(
        array &$structure,
        ClassMetadata $classMeta,
        $entity,
        array $fieldnamesToSkip,
        ?array $fieldnameToDateFormat = []
    ): void {

        $dateFormatFieldDefault = \DateTimeInterface::ISO8601;
        $columnNames = $classMeta->getColumnNames();

        foreach ($columnNames as $columnName) {

            $field = $classMeta->getFieldName($columnName);
            if (in_array($field, $fieldnamesToSkip)) {
                continue;
            }

            $type = $classMeta->getTypeOfField($field);
            $method = 'get' . ucfirst($field);

            switch ($type) {
                case 'bool':
                case 'boolean':
                    $method = 'is' . ucfirst($field);
                    !method_exists($entity, $method) && $method = 'get' . ucfirst($field);
                    // no break!

                default :
                    $structure[$field] = method_exists($entity, $method)
                        ? $entity->$method()
                        : $entity->$field;
            }

            // $type is equal to 'datetime' or 'date'
            if ($structure[$field] instanceof \DateTimeInterface) {
                $formatDefinition = array_key_exists($field, $fieldnameToDateFormat)
                    ? $fieldnameToDateFormat[$field]
                    : $dateFormatFieldDefault;

                $structure[$field] = $structure[$field]->format($formatDefinition);
            }
        }
    }

    /**
     * @param array $structure
     * @param ClassMetadata $classMeta
     * @param $entity
     * @param array $fieldnamesToSkip
     * @throws \Exception
     */
    private function enrichStructureByEntityAssociations(
        array &$structure,
        ClassMetadata $classMeta,
        $entity,
        array $fieldnamesToSkip
    ): void {

        $associationColumns = $classMeta->getAssociationMappings();

        foreach ($associationColumns as $columnAssoc) {
            $field = $columnAssoc['fieldName'];
            if (in_array($field, $fieldnamesToSkip)) {
                continue;
            }

            $structure[$field] = $this->getRelatedEntityAsArray($classMeta, $field, $entity);
        }
    }

    /**
     * @param ClassMetadata $classMeta
     * @param string $field
     * @param $entity
     * @return array|Closure|null
     * @throws \Exception
     */
    private function getRelatedEntityAsArray(ClassMetadata $classMeta, string $field, $entity)
    {
        $method = 'get' . ucfirst($field);
        $relation = method_exists($entity, $method)
            ? $entity->$method()
            : $entity->$field;

        if ($classMeta->isCollectionValuedAssociation($field)) {
            return function () use ($relation) {
                $objects = [];
                foreach ($relation as $object) {
                    $objects[] = $this->convertEntityToArray($object);
                }
                return $objects;
            };
        }

        // All code below will only be execute in case the relation is a single valued association

        if ($relation) {
            return function() use ($relation) {
                return $this->convertEntityToArray($relation);
            };
        }

        // if the current field is the inverse side of a relation, the value can always be null
        // and therefore no exception has to be thrown!
        if ($classMeta->isAssociationInverseSide($field)) {
            return null;
        }

        return function () use ($classMeta, $field, $entity) {
            $allJoinColumnsNullable = array_reduce(
                $classMeta->getAssociationMapping($field)['joinColumns'],
                function ($carry, $fieldSpec) {
                    return $carry && (bool)$fieldSpec['nullable'];
                },
                true
            );

            // If all join columns (on owning side), which representing the association field in the 
            // entity, are nullable, then the associated entity is optional and can be null
            if ($allJoinColumnsNullable) {
                return null;
            }

            // In all other cases we expect data from the relation
            throw new \Exception(
                sprintf(
                    'Got no related entity within field "%s" in entity of type %s!',
                    $field,
                    get_class($entity)
                ),
                1609881209
            );
        };
    }

    /**
     * This helper method is useful to put data to an entity during a mutation call
     * @param $entity
     * @param array $data
     * @throws \Exception
     */
    protected static function arrayToEntityBySetter(&$entity, array $data)
    {
        foreach ($data as $setter => $value) {
            if (!method_exists($entity, $setter)) {
                throw new \Exception(
                    sprintf(
                        'No setter named "%s" in entity of type "%s" available',
                        $setter,
                        get_class($entity)
                    ),
                    1609920309
                );
            }
            if (!is_callable([$entity, $setter])) {
                throw new \Exception(
                    sprintf(
                        'Setter named "%s" of entity "%s" is not callable',
                        $setter,
                        get_class($entity)
                    ),
                    1609920511
                );
            }
            $entity->$setter($value);
        }
    }
}
