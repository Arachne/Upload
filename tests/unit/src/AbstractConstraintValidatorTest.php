<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractConstraintValidatorTest extends Unit
{
    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ConstraintValidatorInterface
     */
    protected $validator;

    protected $group;
    protected $metadata;
    protected $object;
    protected $value;
    protected $root;
    protected $propertyPath;
    protected $constraint;
    protected $defaultTimezone;

    protected function _before()
    {
        $this->group = 'MyGroup';
        $this->metadata = null;
        $this->object = null;
        $this->value = 'InvalidValue';
        $this->root = 'root';
        $this->propertyPath = 'property.path';

        // Initialize the context with some constraint so that we can
        // successfully build a violation.
        $this->constraint = new NotNull();

        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        \Locale::setDefault('en');
    }

    protected function createContext()
    {
        if (method_exists($this, 'createMock')) {
            $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
            $validator = $this->createMock('Symfony\Component\Validator\Validator\ValidatorInterface');
            $contextualValidator = $this->createMock('Symfony\Component\Validator\Validator\ContextualValidatorInterface');
        } else {
            $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
            $validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');
            $contextualValidator = $this->getMock('Symfony\Component\Validator\Validator\ContextualValidatorInterface');
        }

        $context = new ExecutionContext($validator, $this->root, $translator);
        $context->setGroup($this->group);
        $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
        $context->setConstraint($this->constraint);

        $validator->expects($this->any())
            ->method('inContext')
            ->with($context)
            ->will($this->returnValue($contextualValidator));

        return $context;
    }

    protected function assertNoViolation()
    {
        $this->assertSame(0, $violationsCount = count($this->context->getViolations()), sprintf('0 violation expected. Got %u.', $violationsCount));
    }

    /**
     * @param $message
     *
     * @return ConstraintViolationAssertion
     */
    protected function buildViolation($message)
    {
        return new ConstraintViolationAssertion($this->context, $message, $this->constraint);
    }

    abstract protected function createValidator();
}

/**
 * @internal
 */
class ConstraintViolationAssertion
{
    /**
     * @var ExecutionContextInterface
     */
    private $context;

    /**
     * @var ConstraintViolationAssertion[]
     */
    private $assertions;

    private $message;
    private $parameters = [];
    private $invalidValue = 'InvalidValue';
    private $propertyPath = 'property.path';
    private $translationDomain;
    private $plural;
    private $code;
    private $constraint;
    private $cause;

    public function __construct(ExecutionContextInterface $context, $message, Constraint $constraint = null, array $assertions = [])
    {
        $this->context = $context;
        $this->message = $message;
        $this->constraint = $constraint;
        $this->assertions = $assertions;
    }

    public function atPath($path)
    {
        $this->propertyPath = $path;

        return $this;
    }

    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setTranslationDomain($translationDomain)
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    public function setInvalidValue($invalidValue)
    {
        $this->invalidValue = $invalidValue;

        return $this;
    }

    public function setPlural($number)
    {
        $this->plural = $number;

        return $this;
    }

    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function setCause($cause)
    {
        $this->cause = $cause;

        return $this;
    }

    public function buildNextViolation($message)
    {
        $assertions = $this->assertions;
        $assertions[] = $this;

        return new self($this->context, $message, $this->constraint, $assertions);
    }

    public function assertRaised()
    {
        $expected = [];
        foreach ($this->assertions as $assertion) {
            $expected[] = $assertion->getViolation();
        }
        $expected[] = $this->getViolation();

        $violations = iterator_to_array($this->context->getViolations());

        \PHPUnit_Framework_Assert::assertSame($expectedCount = count($expected), $violationsCount = count($violations), sprintf('%u violation(s) expected. Got %u.', $expectedCount, $violationsCount));

        reset($violations);

        foreach ($expected as $violation) {
            \PHPUnit_Framework_Assert::assertEquals($violation, current($violations));
            next($violations);
        }
    }

    private function getViolation()
    {
        return new ConstraintViolation(
            null,
            $this->message,
            $this->parameters,
            $this->context->getRoot(),
            $this->propertyPath,
            $this->invalidValue,
            $this->plural,
            $this->code,
            $this->constraint,
            $this->cause
        );
    }
}
