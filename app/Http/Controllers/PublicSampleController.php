<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use App\Models\SampleSet;

class PublicSampleController extends Controller
{
    public function show(string $sampleId)
    {
        if (str_starts_with($sampleId, 'SET-')) {
            $set = SampleSet::where('set_id', $sampleId)
                ->with(['productLine', 'items.productStyle.productLine'])
                ->firstOrFail();

            $checkoutUrl = route('mobile.sample-sets.checkout', $set->set_id);

            return view('public.sample-scan', compact('set', 'checkoutUrl'));
        }

        $sample = Sample::where('sample_id', $sampleId)
            ->with(['productStyle.productLine.unit', 'productStyle.photos'])
            ->firstOrFail();

        $checkoutUrl = route('mobile.samples.checkout', $sample->sample_id);

        return view('public.sample-scan', compact('sample', 'checkoutUrl'));
    }
}
