<?php

namespace Hgabka\Doctrine\TranslatableBundle\Filter;

use Doctrine\ORM\Query\Expr;
use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\Filter;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;

/**
 * TranslatableFilter
 *
 * @see StringFilter
 */
class TranslatableFilter extends Filter
{
    public const TRIM_NONE = 0;
    public const TRIM_LEFT = 1;
    public const TRIM_RIGHT = 2;
    public const TRIM_BOTH = self::TRIM_LEFT | self::TRIM_RIGHT;

    public const CHOICES = [
        StringOperatorType::TYPE_CONTAINS => 'LIKE',
        StringOperatorType::TYPE_STARTS_WITH => 'LIKE',
        StringOperatorType::TYPE_ENDS_WITH => 'LIKE',
        StringOperatorType::TYPE_NOT_CONTAINS => 'NOT LIKE',
        StringOperatorType::TYPE_EQUAL => '=',
        StringOperatorType::TYPE_NOT_EQUAL => '<>',
    ];

    /**
     * Filtering types do not make sense for searching by empty value.
     */
    private const MEANINGLESS_TYPES = [
        StringOperatorType::TYPE_CONTAINS,
        StringOperatorType::TYPE_STARTS_WITH,
        StringOperatorType::TYPE_ENDS_WITH,
        StringOperatorType::TYPE_NOT_CONTAINS,
    ];

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
    public function filter(ProxyQueryInterface $queryBuilder, string $alias, string $field, FilterData $data)
    {
        if (!$data || !$data->hasValue()) {
            return;
        }

        $value = trim($data->getValue());

        if ('' === $value) {
            return;
        }

        $type = !$data->getType() ? ChoiceType::TYPE_CONTAINS : $data->getType();
        $operator = $this->getOperator((int) $data->getType());

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

        if (ChoiceType::TYPE_NOT_CONTAINS === $type) {
            $or->add($queryBuilder->expr()->isNull(sprintf('%s.%s', 'trans', $field)));
        }

        $this->applyWhere($queryBuilder, $or);

        if (ChoiceType::TYPE_EQUAL === $type) {
            $queryBuilder->setParameter($parameterName, $value);
        } else {
            $queryBuilder->setParameter($parameterName, sprintf($this->getOption('format'), $value));
        }
    }

    public function isSearchEnabled(): bool
    {
        return $this->getOption('global_search');
    }

    public function getDefaultOptions(): array
    {
        return [
            'force_case_insensitivity' => false,
            'trim' => self::TRIM_BOTH,
            'allow_empty' => false,
            'global_search' => true,
        ];
    }

    public function getRenderSettings(): array
    {
        return [ChoiceType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    /**
     * Does the query builder have a translation join
     *
     * @param ProxyQueryInterface $queryBuilder
     * @param mixed               $alias
     *
     * @return bool
     */
    private function hasJoin(ProxyQueryInterface $queryBuilder, string $alias): bool
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

    private function getOperator(int $type): string
    {
        if (!isset(self::CHOICES[$type])) {
            throw new \OutOfRangeException(sprintf('The type "%s" is not supported, allowed one are "%s".', $type, implode('", "', array_keys(self::CHOICES))));
        }

        return self::CHOICES[$type];
    }

    private function trim(string $string): string
    {
        $trimMode = $this->getOption('trim', self::TRIM_BOTH);

        if (0 !== ($trimMode & self::TRIM_LEFT)) {
            $string = ltrim($string);
        }

        if (0 !== ($trimMode & self::TRIM_RIGHT)) {
            $string = rtrim($string);
        }

        return $string;
    }
}
