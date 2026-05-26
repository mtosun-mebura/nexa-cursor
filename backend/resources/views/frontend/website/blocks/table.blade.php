@php $content = $block['data']['content'] ?? []; @endphp
@if(!empty($content))
<div class="website-block website-block-table mb-6 overflow-x-auto">
    <table class="min-w-full border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($content as $rowIndex => $row)
            <tr class="{{ $rowIndex === 0 ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }}">
                @foreach($row as $cell)
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 {{ $rowIndex === 0 ? 'font-semibold' : '' }}">
                    {!! $cell !!}
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
