<?php

namespace Hgabka\Doctrine\TranslatableBundle\Tests\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Hgabka\Doctrine\Translatable\EventListener\TranslatableListener;
use Hgabka\Doctrine\Translatable\Mapping\Driver\AttributeDriver;
use Hgabka\Doctrine\TranslatableBundle\Filter\TranslatableFilter;
use Hgabka\Doctrine\TranslatableBundle\Tests\Fixtures\Article;
use Metadata\MetadataFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;

class TranslatableFilterTest extends TestCase
{
    private TranslatableListener $listener;

    private TranslatableFilter $filter;

    protected function setUp(): void
    {
        $this->listener = new TranslatableListener(new MetadataFactory(new AttributeDriver()));
        $this->listener->setCurrentLocale('hu');
        $this->filter = new TranslatableFilter($this->listener);
        $this->filter->initialize('title', ['field_name' => 'title']);
        $this->filter->setCondition(TranslatableFilter::CONDITION_AND);
    }

    private function query(): ProxyQuery
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());
        $builder = new QueryBuilder($em);
        $builder->select('o')->from(Article::class, 'o');

        return new ProxyQuery($builder);
    }

    private function dql(ProxyQuery $query): string
    {
        return $query->getQueryBuilder()->getDQL();
    }

    private function parameters(ProxyQuery $query): array
    {
        $parameters = [];
        foreach ($query->getQueryBuilder()->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter->getValue();
        }

        return $parameters;
    }

    public function testDefaultOptions(): void
    {
        $options = $this->filter->getDefaultOptions();

        $this->assertSame(TranslatableFilter::TRIM_BOTH, $options['trim']);
        $this->assertTrue($options['global_search']);
        $this->assertSame(TranslatableFilter::TRIM_LEFT | TranslatableFilter::TRIM_RIGHT, TranslatableFilter::TRIM_BOTH);
        $this->assertTrue($this->filter->isSearchEnabled());
    }

    public function testNothingHappensWithoutAValue(): void
    {
        $query = $this->query();
        $before = $this->dql($query);

        $this->filter->apply($query, FilterData::fromArray(['value' => null]));
        $this->filter->apply($query, FilterData::fromArray(['value' => '   ']));

        $this->assertSame($before, $this->dql($query));
    }

    public function testTranslationIsJoinedForTheCurrentLocaleAndSearched(): void
    {
        $query = $this->query();

        $this->filter->apply($query, FilterData::fromArray(['value' => ' news ', 'type' => StringOperatorType::TYPE_CONTAINS]));

        $dql = $this->dql($query);
        $this->assertStringContainsString('INNER JOIN o.translations trans WITH trans.locale = :', $dql);
        $this->assertStringContainsString('trans.title LIKE :', $dql);
        $this->assertSame(['hu', '%news%'], array_values($this->parameters($query)));
    }

    public function testTheTypeDefaultsToContains(): void
    {
        $query = $this->query();

        $this->filter->apply($query, FilterData::fromArray(['value' => 'x']));

        $this->assertStringContainsString('LIKE', $this->dql($query));
        $this->assertSame('%x%', array_values($this->parameters($query))[1]);
    }

    #[DataProvider('operators')]
    public function testOperatorsAndValueFormats(int $type, string $sqlOperator, string $expectedValue): void
    {
        $query = $this->query();

        $this->filter->apply($query, FilterData::fromArray(['value' => 'abc', 'type' => $type]));

        $this->assertStringContainsString('trans.title ' . $sqlOperator . ' :', $this->dql($query));
        $this->assertSame($expectedValue, array_values($this->parameters($query))[1]);
    }

    public static function operators(): array
    {
        return [
            'contains' => [StringOperatorType::TYPE_CONTAINS, 'LIKE', '%abc%'],
            'starts with' => [StringOperatorType::TYPE_STARTS_WITH, 'LIKE', 'abc%'],
            'ends with' => [StringOperatorType::TYPE_ENDS_WITH, 'LIKE', '%abc'],
            'not contains' => [StringOperatorType::TYPE_NOT_CONTAINS, 'NOT LIKE', '%abc%'],
            'equal' => [StringOperatorType::TYPE_EQUAL, '=', 'abc'],
            'not equal' => [StringOperatorType::TYPE_NOT_EQUAL, '<>', 'abc'],
        ];
    }

    public function testNotContainsAlsoMatchesMissingTranslations(): void
    {
        $query = $this->query();

        $this->filter->apply($query, FilterData::fromArray(['value' => 'abc', 'type' => StringOperatorType::TYPE_NOT_CONTAINS]));

        $this->assertStringContainsString('trans.title IS NULL', $this->dql($query));
    }

    public function testTheTranslationJoinIsAddedOnlyOnce(): void
    {
        $query = $this->query();
        $data = FilterData::fromArray(['value' => 'abc', 'type' => StringOperatorType::TYPE_EQUAL]);

        $this->filter->apply($query, $data);
        $this->filter->apply($query, $data);

        $this->assertSame(1, substr_count($this->dql($query), 'INNER JOIN'));
    }

    public function testUnsupportedOperatorsAreRejected(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->filter->apply($this->query(), FilterData::fromArray(['value' => 'abc', 'type' => 999]));
    }
}
