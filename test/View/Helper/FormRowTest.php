<?php

declare(strict_types=1);

namespace LaminasTest\Form\View\Helper;

use Laminas\Form\Element;
use Laminas\Form\Element\Captcha;
use Laminas\Form\Exception\InvalidArgumentException;
use Laminas\Form\View\Helper\FormRow as FormRowHelper;
use Laminas\I18n\Translator\Translator;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Validator\Date;
use Laminas\View\Resolver\TemplatePathStack;

use function explode;
use function get_class;
use function uniqid;

/**
 * @property FormRowHelper $helper
 */
final class FormRowTest extends AbstractCommonTestCase
{
    protected function setUp(): void
    {
        $this->helper = new FormRowHelper();
        parent::setUp();
    }

    public function testCanGenerateLabel(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString('>The value for foo:<', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testCanCreateLabelValueBeforeInput(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $this->helper->setLabelPosition('prepend');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString('<label><span>The value for foo:</span><', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testCanCreateLabelValueAfterInput(): void
    {
        $element = new Element('foo');
        $element->setOptions([
            'label' => 'The value for foo:',
        ]);
        $this->helper->setLabelPosition('append');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString('<label><input', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testCanOverrideLabelPosition(): void
    {
        $fooElement = new Element('foo');
        $fooElement->setOptions([
            'label'         => 'The value for foo:',
            'label_options' => [
                'label_position' => 'prepend',
            ],
        ]);

        $barElement = new Element('bar');
        $barElement->setOptions([
            'label' => 'The value for bar:',
        ]);

        $this->helper->setLabelPosition('append');

        $fooMarkup = $this->helper->render($fooElement);
        $this->assertStringContainsString('<label><span>The value for foo:</span><', $fooMarkup);
        $this->assertStringContainsString('</label>', $fooMarkup);

        $barMarkup = $this->helper->render($barElement);
        $this->assertStringContainsString('<label><', $barMarkup);
        $this->assertStringContainsString('<span>The value for bar:</span></label>', $barMarkup);
    }

    public function testCanRenderRowLabelAttributes(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $element->setLabelAttributes(['class' => 'bar']);
        $this->helper->setLabelPosition('append');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString('<label class="bar">', $markup);
    }

    public function testCanCreateMarkupWithoutLabel(): void
    {
        $element = new Element('foo');
        $element->setAttribute('type', 'text');
        $markup = $this->helper->render($element);
        $this->assertMatchesRegularExpression('/<input name="foo" type="text"[^\/>]*\/?>/', $markup);
    }

    public function testIgnoreLabelForHidden(): void
    {
        $element = new Element\Hidden('foo');
        $element->setLabel('My label');
        $markup = $this->helper->render($element);
        $this->assertMatchesRegularExpression('/<input type="hidden" name="foo" value=""[^\/>]*\/?>/', $markup);
    }

    public function testCanHandleMultiCheckboxesCorrectly(): void
    {
        $options = [
            'This is the first label'  => 'value1',
            'This is the second label' => 'value2',
            'This is the third label'  => 'value3',
        ];

        $element = new Element\MultiCheckbox('foo');
        $element->setAttribute('type', 'multi_checkbox');
        $element->setAttribute('options', $options);
        $element->setLabel('This is a multi-checkbox');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString('<fieldset>', $markup);
        $this->assertStringContainsString('<legend>', $markup);
        $this->assertStringContainsString('<label>', $markup);
    }

    public function testRenderAttributeId(): void
    {
        $element = new Element\Text('foo');
        $element->setAttribute('type', 'text');
        $element->setAttribute('id', 'textId');
        $element->setLabel('This is a text');
        $markup = $this->helper->render($element);
        $this->assertStringContainsString('<label for="textId">This is a text</label>', $markup);
        $this->assertStringContainsString('<input type="text" name="foo" id="textId"', $markup);
    }

    public function testCanRenderErrors(): void
    {
        $element = new Element('foo');
        $element->setMessages([
            'First error message',
            'Second error message',
            'Third error message',
        ]);

        $markup = $this->helper->render($element);
        // @codingStandardsIgnoreStart
        $this->assertMatchesRegularExpression('#<ul>\s*<li>First error message</li>\s*<li>Second error message</li>\s*<li>Third error message</li>\s*</ul>#s', $markup);
        // @codingStandardsIgnoreEnd
    }

    public function testDoesNotRenderErrorsListIfSetToFalse(): void
    {
        $element = new Element('foo');
        $element->setMessages([
            'First error message',
            'Second error message',
            'Third error message',
        ]);

        $markup = $this->helper->setRenderErrors(false)->render($element);
        $this->assertMatchesRegularExpression(
            '/<input name="foo" class="input-error" type="text" [^\/>]*\/?>/',
            $markup
        );
    }

    public function testCanModifyDefaultErrorClass(): void
    {
        $element = new Element('foo');
        $element->setMessages([
            'Error message',
        ]);

        $markup = $this->helper->setInputErrorClass('custom-error-class')->render($element);
        $this->assertMatchesRegularExpression(
            '/<input name="foo" class="custom-error-class" type="text" [^\/>]*\/?>/',
            $markup
        );
    }

    public function testDoesNotOverrideClassesIfAlreadyPresentWhenThereAreErrors(): void
    {
        $element = new Element('foo');
        $element->setMessages([
            'Error message',
        ]);
        $element->setAttribute('class', 'foo bar');

        $markup = $this->helper->setInputErrorClass('custom-error-class')->render($element);
        $this->assertMatchesRegularExpression(
            '/<input name="foo" class="foo\&\#x20\;bar\&\#x20\;custom-error-class" type="text" [^\/>]*\/?>/',
            $markup
        );
    }

    public function testInvokeWithNoElementChainsHelper(): void
    {
        $this->assertSame($this->helper, $this->helper->__invoke());
    }

    public function testLabelWillBeTranslated(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');

        $mockTranslator = $this->createMock(Translator::class);
        $mockTranslator
            ->method('translate')
            ->willReturn('translated content');

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('>translated content<', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);

        // Additional coverage when element's id is set
        $element->setAttribute('id', 'foo');
        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('>translated content<', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testTranslatorMethods(): void
    {
        $translatorMock = $this->createMock(Translator::class);
        $this->helper->setTranslator($translatorMock, 'foo');

        $this->assertEquals($translatorMock, $this->helper->getTranslator());
        $this->assertEquals('foo', $this->helper->getTranslatorTextDomain());
        $this->assertTrue($this->helper->hasTranslator());
        $this->assertTrue($this->helper->isTranslatorEnabled());

        $this->helper->setTranslatorEnabled(false);
        $this->assertFalse($this->helper->isTranslatorEnabled());
    }

    public function testLabelWillBeTranslatedOnceWithoutId(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');

        $mockTranslator = $this->createMock(TranslatorInterface::class);
        $mockTranslator->expects($this->once())
            ->method('translate')
            ->willReturn('translated content');

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('>translated content<', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testLabelWillBeTranslatedOnceWithId(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');
        $element->setAttribute('id', 'foo');

        $mockTranslator = $this->createMock(TranslatorInterface::class);
        $mockTranslator->expects($this->once())
            ->method('translate')
            ->willReturn('translated content');

        $this->helper->setTranslator($mockTranslator);
        $this->assertTrue($this->helper->hasTranslator());

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('>translated content<', $markup);
        $this->assertStringContainsString('<label', $markup);
        $this->assertStringContainsString('</label>', $markup);
    }

    public function testSetLabelPositionInputRandomRaisesException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper->setLabelPosition(uniqid('non_existant_'));
    }

    public function testGetLabelPositionReturnsDefaultPrepend(): void
    {
        $labelPosition = $this->helper->getLabelPosition();
        $this->assertEquals('prepend', $labelPosition);
    }

    public function testGetLabelPositionReturnsAppend(): void
    {
        $this->helper->setLabelPosition('append');
        $labelPosition = $this->helper->getLabelPosition();
        $this->assertEquals('append', $labelPosition);
    }

    public function testGetRenderErrorsReturnsDefaultTrue(): void
    {
        $renderErrors = $this->helper->getRenderErrors();
        $this->assertTrue($renderErrors);
    }

    public function testGetRenderErrorsSetToFalse(): void
    {
        $this->helper->setRenderErrors(false);
        $renderErrors = $this->helper->getRenderErrors();
        $this->assertFalse($renderErrors);
    }

    public function testSetLabelAttributes(): void
    {
        $this->helper->setLabelAttributes(['foo', 'bar']);
        $this->assertEquals([0 => 'foo', 1 => 'bar'], $this->helper->getLabelAttributes());
    }

    public function testWhenUsingIdAndLabelBecomesEmptyRemoveSpan(): void
    {
        $element = new Element('foo');
        $element->setLabel('The value for foo:');

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('<span', $markup);
        $this->assertStringContainsString('</span>', $markup);

        $element->setAttribute('id', 'foo');

        $markup = $this->helper->__invoke($element);
        $this->assertStringNotContainsString('<span', $markup);
        $this->assertStringNotContainsString('</span>', $markup);
    }

    public function testShowErrorInMultiCheckbox(): void
    {
        $element = new Element\MultiCheckbox('hobby');
        $element->setLabel('Hobby');
        $element->setValueOptions([
            '0' => 'working',
            '1' => 'coding',
        ]);
        $element->setMessages([
            'Error message',
        ]);

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('<ul><li>Error message</li></ul>', $markup);
    }

    public function testShowErrorInRadio(): void
    {
        $element = new Element\Radio('direction');
        $element->setLabel('Direction');
        $element->setValueOptions([
            '0' => 'programming',
            '1' => 'design',
        ]);
        $element->setMessages([
            'Error message',
        ]);

        $markup = $this->helper->__invoke($element);
        $this->assertStringContainsString('<ul><li>Error message</li></ul>', $markup);
    }

    public function testErrorShowTwice(): void
    {
        $element = new Element\Date('birth');
        $element->setFormat('Y-m-d');
        $element->setValue('2010.13');

        $validator = new Date();
        $validator->isValid($element->getValue());
        $element->setMessages($validator->getMessages());

        $markup = $this->helper->__invoke($element);
        $this->assertCount(
            2,
            explode('<ul><li>The input does not appear to be a valid date</li></ul>', $markup)
        );
    }

    public function testInvokeWithNoRenderErrors(): void
    {
        $mock = $this->getMockBuilder(get_class($this->helper))
            ->setMethods(['setRenderErrors'])
            ->getMock();
        $mock->expects($this->never())
                ->method('setRenderErrors');

        $mock->__invoke(new Element('foo'));
    }

    public function testInvokeWithRenderErrorsTrue(): void
    {
        $mock = $this->getMockBuilder(get_class($this->helper))
            ->setMethods(['setRenderErrors'])
            ->getMock();
        $mock->expects($this->once())
                ->method('setRenderErrors')
                ->with(true);

        $mock->__invoke(new Element('foo'), null, true);
    }

    public function testAppendLabelEvenIfElementHasId(): void
    {
        $element = new Element('foo');
        $element->setAttribute('id', 'bar');
        $element->setLabel('Baz');

        $this->helper->setLabelPosition('append');
        $markup = $this->helper->render($element);
        $this->assertMatchesRegularExpression(
            '#^<input name="foo" id="bar" type="text" value=""\/?><label for="bar">Baz</label>$#',
            $markup
        );
    }

    public function testUsePartialView(): void
    {
        $element = new Element('fooname');
        $element->setLabel('foolabel');
        $partial = 'formrow-partial.phtml';

        $resolver = $this->renderer->resolver();
        $this->assertInstanceOf(TemplatePathStack::class, $resolver);
        $resolver->addPath(__DIR__ . '/_templates');
        $markup = $this->helper->__invoke($element, null, null, $partial);
        $this->assertStringContainsString('fooname', $markup);
        $this->assertStringContainsString('foolabel', $markup);

        $this->assertSame($partial, $this->helper->getPartial());
    }

    public function testAssertButtonElementDoesNotRenderLabelTwice(): void
    {
        $element = new Element\Button('button');
        $element->setLabel('foo');

        $markup = $this->helper->render($element);
        $this->assertMatchesRegularExpression(
            '#^<button type="button" name="button" value=""\/?>foo</button>$#',
            $markup
        );
    }

    public function testAssertLabelHtmlEscapeIsOnByDefault(): void
    {
        $element      = new Element('fooname');
        $escapeHelper = $this->renderer->getHelperPluginManager()->get('escapeHtml');

        $label = '<span>foo</span>';
        $element->setLabel($label);

        $markup = $this->helper->__invoke($element);

        $this->assertStringContainsString($escapeHelper($label), $markup);
    }

    public function testCanDisableLabelHtmlEscape(): void
    {
        $label   = '<span>foo</span>';
        $element = new Element('fooname');
        $element->setLabel($label);
        $element->setLabelOptions(['disable_html_escape' => true]);

        $markup = $this->helper->__invoke($element);

        $this->assertStringContainsString($label, $markup);
    }

    public function testCanSetLabelPositionBeforeInvoke(): void
    {
        $element = new Element('foo');

        $this->helper->setLabelPosition('append');
        $this->helper->__invoke($element);

        $this->assertSame('append', $this->helper->getLabelPosition());
    }

    /**
     * @covers \Laminas\Form\View\Helper\FormRow::render
     */
    public function testCanSetLabelPositionViaRender(): void
    {
        $element = new Element('foo');
        $element->setAttribute('id', 'bar');
        $element->setLabel('Baz');

        $markup = $this->helper->render($element, 'append');
        $this->assertMatchesRegularExpression(
            '#^<input name="foo" id="bar" type="text" value=""\/?><label for="bar">Baz</label>$#',
            $markup
        );

        $markup = $this->helper->render($element, 'prepend');
        $this->assertMatchesRegularExpression(
            '#^<label for="bar">Baz</label><input name="foo" id="bar" type="text" value=""\/?>$#',
            $markup
        );
    }

    public function testSetLabelPositionViaRenderIsNotCached(): void
    {
        $labelPositionBeforeRender = $this->helper->getLabelPosition();
        $element                   = new Element('foo');

        $this->helper->render($element, 'append');
        $this->assertSame($labelPositionBeforeRender, $this->helper->getLabelPosition());

        $this->helper->render($element, 'prepend');
        $this->assertSame($labelPositionBeforeRender, $this->helper->getLabelPosition());
    }

    /**
     * @covers \Laminas\Form\View\Helper\FormRow::__invoke
     */
    public function testCanSetLabelPositionViaInvoke(): void
    {
        $element = new Element('foo');
        $element->setAttribute('id', 'bar');
        $element->setLabel('Baz');

        $markup = $this->helper->__invoke($element, 'append');
        $this->assertMatchesRegularExpression(
            '#^<input name="foo" id="bar" type="text" value=""\/?><label for="bar">Baz</label>$#',
            $markup
        );

        $markup = $this->helper->__invoke($element, 'prepend');
        $this->assertMatchesRegularExpression(
            '#^<label for="bar">Baz</label><input name="foo" id="bar" type="text" value=""\/?>$#',
            $markup
        );
    }

    /**
     * @covers \Laminas\Form\View\Helper\FormRow::__invoke
     */
    public function testSetLabelPositionViaInvokeIsNotCached(): void
    {
        $labelPositionBeforeRender = $this->helper->getLabelPosition();
        $element                   = new Element('foo');

        $this->helper->__invoke($element, 'append');
        $this->assertSame($labelPositionBeforeRender, $this->helper->getLabelPosition());

        $this->helper->__invoke($element, 'prepend');
        $this->assertSame($labelPositionBeforeRender, $this->helper->getLabelPosition());
    }

    public function testLabelOptionAlwaysWrapDefaultsToFalse(): void
    {
        $element = new Element('foo');
        $this->assertEmpty($element->getLabelOption('always_wrap'));
    }

    public function testCanSetOptionToWrapElementInLabel(): void
    {
        $element = new Element('foo', [
            'label_options' => [
                'always_wrap' => true,
            ],
        ]);
        $element->setAttribute('id', 'bar');
        $element->setLabel('baz');

        $markup = $this->helper->render($element);
        $this->assertMatchesRegularExpression(
            '#^<label><span>baz</span><input name="foo" id="bar" type="text" value=""\/?></label>$#',
            $markup
        );
    }

    /**
     * @group issue-7030
     */
    public function testWrapFieldsetAroundCaptchaWithLabel(): void
    {
        $this->assertMatchesRegularExpression(
            '#^<fieldset><legend>baz<\/legend>'
            . 'Please type this word backwards <b>[a-z0-9]{8}<\/b>'
            . '<input name="captcha&\#x5B;id&\#x5D;" type="hidden" value="[a-z0-9]{32}"\/?>'
            . '<input name="captcha&\#x5B;input&\#x5D;" type="text"\/?>'
            . '<\/fieldset>$#',
            $this->helper->render(new Captcha('captcha', [
                'captcha' => ['class' => 'dumb'],
                'label'   => 'baz',
            ]))
        );
    }
}
