<template>
	<view>
		<block v-if="value.message_type == 0">
			<view class="parse">
				<u-parse :show-with-animation="true" :tag-style="{img:'width:60px;height:60px;'}" :selectable="true" :html="value.message"></u-parse>
			</view>
		</block>
		<block v-else-if="value.message_type == 2">
			<view class="parse">
				<u-parse :show-with-animation="true" :selectable="true" :html="'<a target=_blank href=' + value.message + '>点击下载' + value.suffix + '文件</a>'"></u-parse>
			</view>
		</block>
		<block v-else-if="value.message_type == 1">
			<image @click="preimage(value.message)" class="image-message" :src="value.message" mode="widthFix"></image>
		</block>
		<block v-else-if="value.message_type == 4 || value.message_type == 5">
			<view @click="preProject" class="project_item">
				<image :src="value.message.logo"></image>
				<view class="project_item_body">
					<view class="project_item_title">{{value.message.subject}}</view>
					<view v-if="value.message.note" class="project_item_note">{{value.message.note}}</view>
					<view class="project_item_price">
						<text v-if="value.message.price">￥{{value.message.price}}</text>
						<text v-if="value.message.number">x{{value.message.number}}</text>
					</view>
				</view>
			</view>
		</block>
		<block v-else-if="value.type == 'file'">
			<view class="file-message" @click="url(value.message.url, '下载地址')">
				<image class="file-suffix" :src="value.suffixImg"></image>
				<view class="file-message-box">
					<view class="file-name">{{value.message.suffix}}文件</view>
					<view class="file-size">{{value.message.size}}</view>
				</view>
				<view class="down-file">
					<u-button type="primary" @click="url(value.message.url, '下载地址')" size="mini">下载</u-button>
				</view>
			</view>
		</block>
	</view>
</template>

<script>
	export default {
		name: "message",
		props: {
			value: {
				type: Object,
				required: true
			}
		},
		methods: {
			preimage: function (url) {
				uni.previewImage({
					urls: [url],
					fail: function() {
						uni.showToast({
							title: '预览图片失败,请重试!',
							icon: 'none'
						})
					}
				});
			},
			preProject: function() {
				uni.showToast({
					title: '请在PC端点击订单或商品卡片',
					icon: 'none'
				})
			},
			url: function (url, title = '链接') {
				// #ifdef H5
				window.open(url);
				return;
				// #endif
				
				uni.setClipboardData({
				    data: url,
				    success: function () {
						uni.showToast({
							title: title + '已复制到剪切板,请在浏览器中打开',
							icon: 'none'
						})
				    }
				});
			},
			distributionGroupSelect: function(id) {
				var that = this
				that.ws.pageFun(function(){
					that.ws.send({
						c: 'Message',
						a: 'distributionCsr',
						data: {
							group_id: id
						}
					})
				})
			},
			openSession: function(id) {
				this.ws.pageFun(() => {
					this.ws.send({ c: 'Message', a: 'openSession', data: { 'id': id, 'type': 'user' } })
				})
			},
			kbsClick: function(id) {
				this.ws.pageFun(() => {
					this.ws.send({
						c: 'Message',
						a: 'loadKbs',
						data: {
							id: id
						}
					})
				})
			},
			manualCsr: function() {
				this.ws.pageFun(() => {
					this.ws.send({
						c: 'Message',
						a: 'distributionCsr',
						data: {}
					})
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
.parse {
	font-size: 30rpx;
}
.image-message {
	display: block;
	width: 320rpx;
	margin: -10rpx;
}
.file-suffix {
	width: 100rpx !important;
	height: 100rpx !important;
	padding: 8rpx;
}
.file-message {
	height: 100rpx;
	display: flex;
	align-items: center;
}
.file-message view {
	margin: 6px 0 0 6px;
	font-size: 28rpx;
}
.file-message .file-name {
    font-weight: bold;
    margin: 0;
    color: #3F3F3F;
}
.file-message .file-size {
    margin: 0;
    color: #999999;
}
.wrapper .message-item.me .file-name {
    color: #FFFFFF;
}
.wrapper .message-item.me .file-size {
    color: #EBEBEB;
}
.file-message .down-file {
    font-size: 12px;
    padding: 0 10px;
}
.fastim-kbs {
	font-size: 30rpx;
}
.csr-group-none {
	font-weight: bold;
	font-size: 28rpx;
	margin-top: 20rpx;
}
.csr-group-name {
	padding-top: 18rpx;
	font-weight: bold;
	font-size: 28rpx;
}
.kbs-items, .not-included {
	padding-top: 20rpx;
}
.fastim-color-blue {
    color: #6388fb !important;
}
.project_item {
	background-color: #FFFFFF;
	display: flex;
	align-items: center;
	padding: 16rpx 8rpx 16rpx 16rpx;
}
.project_item image {
	width: 100rpx;
	height: 100rpx;
	min-width: 100rpx;
}
.project_item_body {
	height: 130rpx;
	width: 96%;
	margin: 0 16rpx;
}
.project_item_title {
	font-size: 28rpx;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	line-height: 30rpx;
	height: 60rpx;
	color: #181818;
}

.project_item_note {
	font-size: 26rpx;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	line-height: 30rpx;
	color: #999999;
	margin-top: 4rpx;
}
.project_item_price text:last-child {
	margin-left: 16rpx;
	color: #999999;
	font-size: 24rpx;
}

.project_item_price text:first-child {
	margin-left: -6rpx;
	font-size: 30rpx;
	color: rgba(231, 76, 60, 1);
}
</style>
