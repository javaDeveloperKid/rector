<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\ValueObject\PhpDocNode;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use Rector\BetterPhpDocParser\Contract\PhpDocNode\TagAwareNodeInterface;
use Rector\BetterPhpDocParser\Utils\ArrayItemStaticHelper;
use Rector\BetterPhpDocParser\ValueObject\TagValueNodeConfiguration;
use Rector\PhpdocParserPrinter\Attributes\AttributesTrait;
use Rector\PhpdocParserPrinter\Contract\AttributeAwareInterface;

abstract class AbstractTagValueNode implements AttributeAwareInterface, PhpDocTagValueNode
{
    use AttributesTrait;

    /**
     * @var mixed[]
     */
    protected $items = [];

    /**
     * @var TagValueNodeConfiguration
     */
    protected $tagValueNodeConfiguration;

    /**
     * @param mixed[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * Generic fallback
     */
    public function __toString(): string
    {
        return $this->printItems($this->items);
    }

    /**
     * @return mixed[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param mixed $value
     */
    public function changeItem(string $key, $value): void
    {
        $this->items[$key] = $value;
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * @param mixed[] $contentItems
     * @return mixed[]
     */
    public function filterOutMissingItems(array $contentItems): array
    {
        if ($this->tagValueNodeConfiguration->getOrderedVisibleItems() === null) {
            return $contentItems;
        }

        return ArrayItemStaticHelper::filterAndSortVisibleItems(
            $contentItems,
            $this->tagValueNodeConfiguration->getOrderedVisibleItems()
        );
    }

    /**
     * @param mixed[] $items
     */
    protected function printItems(array $items): string
    {
        $items = $this->filterOutMissingItems($items);

        return $this->printContentItems($items);
    }

    /**
     * @param string[] $items
     */
    protected function printContentItems(array $items): string
    {
        $items = $this->filterOutMissingItems($items);

        // remove null values
        $items = array_filter($items);

        if ($items === []) {
            return '';
        }

        // print array value to string
        foreach ($items as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $arrayItemAsString = $this->printArrayItem($value, $key, $this->tagValueNodeConfiguration);

            /** @var string $key */
            $items[$key] = $arrayItemAsString;
        }

        return sprintf(
            '(%s%s%s)',
            $this->tagValueNodeConfiguration->hasNewlineAfterOpening() ? PHP_EOL : '',
            implode(', ', $items),
            $this->tagValueNodeConfiguration->hasNewlineBeforeClosing() ? PHP_EOL : ''
        );
    }

    /**
     * @param PhpDocTagValueNode[] $tagValueNodes
     */
    protected function printNestedTag(array $tagValueNodes): string
    {
        $tagValueNodesAsString = $this->printTagValueNodesSeparatedByComma($tagValueNodes);
        return sprintf('{%s%s%s%s}', $tagValueNodesAsString);
    }

    /**
     * @param PhpDocTagValueNode[] $tagValueNodes
     */
    private function printTagValueNodesSeparatedByComma(array $tagValueNodes): string
    {
        if ($tagValueNodes === []) {
            return '';
        }

        $itemsAsStrings = [];
        foreach ($tagValueNodes as $tagValueNode) {
            $item = '';
            if ($tagValueNode instanceof TagAwareNodeInterface) {
                $item .= $tagValueNode->getTag();
            }

            $item .= (string) $tagValueNode;

            $itemsAsStrings[] = $item;
        }

        return implode(', ', $itemsAsStrings);
    }
}
