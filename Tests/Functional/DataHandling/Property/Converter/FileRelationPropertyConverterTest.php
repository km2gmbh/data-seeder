<?php

declare(strict_types=1);

namespace KM2\DataSeeder\Tests\Functional\DataHandling\Property\Converter;

use KM2\DataSeeder\DataHandling\Node\NodeFactory;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Converter\FileRelationPropertyConverter;
use KM2\DataSeeder\DataHandling\Property\Property;
use KM2\DataSeeder\DataHandling\Property\PropertyCollection;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileRelationPropertyConverterTest extends FunctionalTestCase
{
    /**
     * @var array<non-empty-string>
     */
    protected array $testExtensionsToLoad = [
        'km2/data-seeder',
    ];

    protected NodeFactory $nodeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Files.csv');

        $this->nodeFactory = $this->get(NodeFactory::class);
    }

    #[Test]
    public function skipRenderingIfTypeIsNotFile(): void
    {
        $subject = $this->get(FileRelationPropertyConverter::class);

        $property = new Property('header', 'Foo');
        $propertyCollection = new PropertyCollection([$property]);
        $node = $this->nodeFactory->build('tt_content', 'test', $propertyCollection, $this->buildPageNode());

        $result = $subject->convert($property, $node);

        self::assertFalse($result);
    }

    #[Test]
    public function convertToFileReferenceIfFilePropertyGiven(): void
    {
        $subject = $this->get(FileRelationPropertyConverter::class);

        $property = new Property('image', [
            ['file' => '{sys_file:test}']
        ]);
        $propertyCollection = new PropertyCollection([$property]);
        $node = $this->nodeFactory->build('tt_content', 'test', $propertyCollection, $this->buildPageNode());

        self::assertCount(0, $node->getChildNodes()->getAll());

        $result = $subject->convert($property, $node);

        self::assertTrue($result);
        self::assertCount(1, $node->getChildNodes()->getAll());
    }

    private function buildPageNode(): NodeInterface
    {
        $propertyCollection = new PropertyCollection([
            new Property('pid', '{pages:root}'),
            new Property('doktype', 1),
            new Property('title', 'Home'),
        ]);

        return $this->nodeFactory->build('pages', 'home', $propertyCollection);
    }
}
