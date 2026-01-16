<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Proof Test.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Proof\Feature;

use Proof\Proof;
use Testing\Concerns\RefreshDatabase;
use Tests\PackageTestCase;

class ProofTest extends PackageTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPackage('Audit', null, true);
        $this->bootPackage('Workflow', null, true);
        $this->bootPackage('Media', null, true);
        $this->bootPackage('Link', null, true);
        $this->bootPackage('Proof', null, true);
    }

    public function test_can_create_source(): void
    {
        $source = Proof::createSource([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Anchor Inc.'
        ]);

        $this->assertDatabaseHas('proof_source', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }

    public function test_can_create_testimonial_fluently(): void
    {
        $source = Proof::createSource(['name' => 'Jane Smith']);

        $testimonial = Proof::testimonial()
            ->source($source)
            ->content('Amazing tool!')
            ->rating(5)
            ->verified()
            ->save();

        $this->assertDatabaseHas('proof_testimonial', [
            'proof_source_id' => $source->id,
            'content' => 'Amazing tool!',
            'rating' => 5,
            'is_verified' => 1
        ]);
    }

    public function test_can_create_case_study_with_sections_and_metrics(): void
    {
        $source = Proof::createSource(['name' => 'Acme Corp']);

        $caseStudy = Proof::caseStudy()
            ->source($source)
            ->title('Success Story')
            ->slug('success-story')
            ->save();

        $caseStudy->sections()->create([
            'title' => 'Background',
            'content' => 'They had no tools.'
        ]);

        $caseStudy->metrics()->create([
            'label' => 'ROI',
            'value' => '500',
            'suffix' => '%'
        ]);

        $this->assertDatabaseHas('proof_case_study', ['title' => 'Success Story']);
        $this->assertDatabaseHas('proof_case_section', ['title' => 'Background']);
        $this->assertDatabaseHas('proof_metric', ['label' => 'ROI', 'value' => '500']);
    }

    public function test_approval_logic(): void
    {
        $source = Proof::createSource(['name' => 'Tester']);
        $testimonial = Proof::testimonial()
            ->source($source)
            ->content('Test')
            ->save();

        $this->assertEquals('pending', $testimonial->fresh()->status);

        Proof::approve($testimonial->id);
        $this->assertEquals('approved', $testimonial->fresh()->status);

        Proof::reject($testimonial->id);
        $this->assertEquals('rejected', $testimonial->fresh()->status);
    }
}
