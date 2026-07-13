<?php

use Illuminate\Support\Facades\Schema;

test('credit_transactions table exists with required columns', function (): void {
    expect(Schema::hasTable('credit_transactions'))->toBeTrue();

    foreach ([
        'id',
        'user_id',
        'reason_id',
        'delta',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('credit_transactions', $column))->toBeTrue("credit_transactions missing column [$column]");
    }
});

test('generations table exists with required columns', function (): void {
    expect(Schema::hasTable('generations'))->toBeTrue();

    foreach ([
        'id',
        'project_id',
        'user_id',
        'status_id',
        'provider_id',
        'prompt_snapshot',
        'constraints_snapshot',
        'idempotency_key',
        'result_path',
        'result_mime_type',
        'result_width_px',
        'result_height_px',
        'failure_reason',
        'credits_charged',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('generations', $column))->toBeTrue("generations missing column [$column]");
    }
});

test('prompt_templates table exists with required columns and 4-tuple unique', function (): void {
    expect(Schema::hasTable('prompt_templates'))->toBeTrue();

    foreach ([
        'id',
        'product_id',
        'category_id',
        'style_id',
        'layout_id',
        'body',
        'version',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('prompt_templates', $column))->toBeTrue("prompt_templates missing column [$column]");
    }
});

test('audit_logs table exists with required columns', function (): void {
    expect(Schema::hasTable('audit_logs'))->toBeTrue();

    foreach ([
        'id',
        'actor_user_id',
        'action_id',
        'target_type',
        'target_id',
        'payload',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('audit_logs', $column))->toBeTrue("audit_logs missing column [$column]");
    }
});
