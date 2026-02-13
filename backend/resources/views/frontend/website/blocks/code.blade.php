@php $code = $block['data']['code'] ?? ''; @endphp
@if($code !== '')
<div class="website-block website-block-code mb-6">
    <pre class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto text-sm font-mono text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700"><code>{{ $code }}</code></pre>
</div>
@endif
