<template>
	<view>
		<view v-if="tips" :style="{position: navbarHeight ? 'fixed':'unset', top: navbarHeight + 'px'}" class="tips">{{tips}}</view>
	</view>
</template>

<script>
	export default {
		name:"common",
		props: {
			tips: {
				type: String,
				default: ''
			},
			navbarHeight: {
				type: [String, Number],
				default: 0
			}
		}
	}
</script>

<style>
.tips {
	position: fixed;
	width: 100%;
	min-height: 80rpx;
	background: rgba(247, 76, 49, 0.9);
	color: #FFFFFF;
	font-size: 28rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 10rpx 30rpx;
	z-index: 99999;
}
</style>
