{$amount = $interval.time.price.amount / 100}
{if $interval.time.start && $interval.time.end && $interval.time.price.currency && $interval.date && $interval.time.price_comment}
    <div>{$interval.date|date_format:"%d-%m"}&nbsp;{$interval.time.start|date_format:$settings.Appearance.time_format}-{$interval.time.end|date_format:$settings.Appearance.time_format}<br>{$interval.time.price_comment}{if $amount > 0}&nbsp;+&nbsp;{include file="common/price.tpl" value=fn_format_price_by_currency($amount, $interval.time.price.currency, $smarty.const.CART_PRIMARY_CURRENCY)}{/if}</div>
{/if}
