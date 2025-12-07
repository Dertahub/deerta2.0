<template>
	<view>
		<view hover-class="session-item-hover" class="session-item">
			<image class="session-avatar" :class="item.avatar_gray" :src="item.avatar" mode="aspectFill"></image>
			<view class="session-info-right">
				<view class="session-info-item">
					<view class="session-info-name">{{item.nickname}}</view>
					<view class="session-info-time im-color-gray">{{item.last_time}}</view>
				</view>
				<view class="session-info-item">
					<view class="session-last-message"><text class="session-user-status">{{(item.user_status ? item.user_status:'')}}</text>{{item.last_message}}</view>
					<view class="session-unread-count" v-if="item.unreadMessagesNumber > 0">{{item.unreadMessagesNumber}}</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: "session",
		props: {
			item: {
				type: Object,
				required: true
			}
		},
		data() {
			return {

			};
		}
	}
</script>

<style lang="scss">
.session-item {
	padding: 24rpx 4%;
	display: flex;
	align-items: center;
	overflow: hidden;
}
.session-avatar {
	height: 100rpx;
	width: 100rpx;
	border-radius: 16rpx;
}
.session-info-right {
	margin-left: 20rpx;
	flex: 1;
	overflow: hidden;
}
.session-info-item {
	display: flex;
	height: 48rpx;
	line-height: 48rpx;
	justify-content: space-between;
}
.session-info-name {
	font-size: 32rpx;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	max-width: 400rpx;
}
.session-info-time {
	overflow: hidden;
	white-space: nowrap;
	max-width: 180rpx;
}
.im-color-gray {
	color: #999999 !important;
}
.session-last-message {
	font-size: 30rpx;
	color: #999999;
	display: inline-block;
	width: 80%;
	overflow: hidden;
	white-space: nowrap;
	vertical-align: middle;
	text-overflow: ellipsis;
}
.session-unread-count {
	height: 34rpx;
	margin-top: 3rpx;
	padding: 0 10rpx;
	background: #f74c31;
	text-align: center;
	line-height: 34rpx;
	border-radius: 50%;
	color: #FFFFFF;
}
.im-img-gray {
    -webkit-filter: grayscale(100%);
    -moz-filter: grayscale(100%);
    -ms-filter: grayscale(100%);
    -o-filter: grayscale(100%);
    filter: grayscale(100%);
    filter: gray;
}
.session-shield {
    background: #D0CFD1 !important;
}
.session-top {
    background: #F8F9F9 !important;
}
.session-item-hover {
	background: #EDEDED !important;
}
</style>
