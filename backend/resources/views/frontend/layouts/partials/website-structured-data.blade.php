@if(!empty($structuredDataGraph))
<script type="application/ld+json">{!! json_encode($structuredDataGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
@endif
