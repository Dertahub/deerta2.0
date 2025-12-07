<template>
	<view>
		<common :tips='commonTips'></common>
		<view class="search-box">
			<u-search @search="search()" @custom="search()" v-model="keywords" class="search-box-u-search" shape="square" placeholder="搜索其实很简单" :clearabled="true" :animation="true"></u-search>
		</view>
		<u-toast ref="uToast" />
		<view class="address-list">
			<u-index-list :scrollTop="scrollTop" :indexList="indexList">
				<view v-for="(item, index) in indexList" :key="index">
					<u-index-anchor :index="item" />
					<navigator :url="'/pages/user/info?id=' + user.user_id" v-for="(user, userIdx) in users[item]" :key="userIdx" class="address-item">
						<image class="address-avatar" :src="user.avatar" mode="aspectFill"></image>
						<view class="address-info">{{user.nickname}}</view>
					</navigator>
				</view>
			</u-index-list>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				keywords: '',
				scrollTop: 0,
				indexList: [],
				users: {},
				commonTips: ''
			}
		},
		onLoad() {
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow() {
			this.ws.checkNetwork(this)
		},
		onPullDownRefresh: function () {
			this.ws.pageFun(this.pageDataLoad, this);
			this.ws.onMessageCallBack.set('address-list', () => {
				this.$refs.uToast.show({
					title: '刷新成功~',
					type: 'success'
				})
				uni.stopPullDownRefresh()
			});
		},
		onPageScroll(e) {
			this.scrollTop = e.scrollTop;
		},
		methods: {
			pageDataLoad: function () {
				this.ws.send({ c: 'Message', a: 'addressList', data: {
					action: 'recently'
				}})
			},
			search: function () {
				if (!this.keywords) {
					this.$refs.uToast.show({
						title: '请输入关键词~',
						type: 'error'
					})
					return ;
				}
				uni.navigateTo({
					url: '/pages/search/search?keywords=' + this.keywords,
					success: () => {
						this.keywords = ''
					}
				})
			},
		}
	}
</script>

<style>
page {
	background: #FFFFFF;
}
.search-box {
	display: flex;
	align-items: center;
	padding: 20rpx 4vw;
}
.search-box .search-box-u-search {
	flex: 1;
}
.popup-menu .popup-menu-item {
	padding: 20rpx 50rpx;
	text-align: center;
	border-bottom: 1px solid #F3F4F6;
}
.popup-menu .popup-menu-item:last-child {
	border: none;
}
.address-item {
	height: 120rpx;
	display: flex;
	align-items: center;
	padding: 0rpx 4%;
}
.address-avatar {
	height: 80rpx;
	width: 80rpx;
	border-radius: 16rpx;
}
.address-info {
	flex: 1;
	height: 120rpx;
	line-height: 120rpx;
	margin-left: 20rpx;
	font-size: 32rpx;
	border-bottom: 1px solid rgba(241, 241, 241, 0.6);
}
.im-bg-grey {
	background-color: #EBEBEB;
	color: #FFFFFF;
}
</style>
