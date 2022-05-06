<?php

namespace Hgabka\Doctrine\TranslatableBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;

/**
 * TranslatableFilter
 *
 * @see StringFilter
 */
class TranslatableFilter extends StringFilter
{
    /**
     * @var TranslatableListener
     */
    private $listener;

    /**
     * Constructor
     *
     * @param TranslatableListener $listener
     */
    public function __construct(TranslatableListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $data)
    {
        if (!$data || !is_array($data) || !array_key_exists('value', $data)) {
            return;
        }

        $data['value'] = trim($data['value']);

        if (0 === strlen($data['value'])) {
            return;
        }

        $data['type'] = !isset($data['type']) ? ChoiceType::TYPE_CONTAINS : $data['type'];
        $operator = $this->getOperator((int) $data['type']);

        if (!$operator) {
            $operator = 'LIKE';
        }

        $entities = $queryBuilder->getRootEntities();
        $classMetadata = $this->listener->getTranslatableMetadata(current($entities));
        $transMetadata = $this->listener->getTranslatableMetadata($classMetadata->targetEntity);

        // Add inner join
        if (!$this->hasJoin($queryBuilder, $alias)) {
            $parameterName = $this->getNewParameterName($queryBuilder);

            $queryBuilder->innerJoin(
                sprintf('%s.%s', $alias, $classMetadata->translations->name),
                'trans',
                Expr\Join::WITH,
                sprintf('trans.%s = :%s', $transMetadata->locale->name, $parameterName)
            );

            $queryBuilder->setParameter($parameterName, $this->listener->getCurrentLocale());
        }

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($queryBuilder);

        $or = $queryBuilder->expr()->orX();

        $or->add(sprintf('%s.%s %s :%s', 'trans', $field, $operator, $parameterName));

        if (ChoiceType::TYPE_NOT_CONTAINS === $data['type']) {
            $or->add($queryBuilder->expr()->isNull(sprintf('%s.%s', 'trans', $field)));
        }

        $this->applyWhere($queryBuilder, $or);

        if (ChoiceType::TYPE_EQUAL === $data['type']) {
            $queryBuilder->setParameter($parameterName, $data['value']);
        } else {
            $queryBuilder->setParameter($parameterName, sprintf($this->getOption('format'), $data['value']));
        }
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function getOperator($type)
    {
        $choices = [
            ChoiceType::TYPE_CONTAINS         => 'LIKE',
            ChoiceType::TYPE_NOT_CONTAINS     => 'NOT LIKE',
            ChoiceType::TYPE_EQUAL            => '=',
        ];

        return $choices[$type] ?? false;
    }

    /**
     * Does the query builder have a translation join
     *
     * @param ProxyQueryInterface $queryBuilder
     * @param mixed               $alias
     *
     * @return bool
     */
    private function hasJoin(ProxyQueryInterface $queryBuilder, $alias)
    {
        $joins = $queryBuilder->getDQLPart('join');

        if (!isset($joins[$alias])) {
            return false;
        }

        foreach ($joins[$alias] as $join) {
            if ('trans' === $join->getAlias()) {
                return true;
            }
        }

        return false;
    }
}
