<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<view class="search-box">
			<u-search @search="search()" @custom="search()" v-model="keywords" class="search-box-u-search" shape="square" placeholder="搜索其实很简单" :clearabled="true" :animation="true"></u-search>
		</view>
		<!-- 会话列表-start -->
		<view class="session-list">
			<block v-for="(item, index) in sessionList" :key="item.id">
				<session @click.native="goToMessage(item.id)" :item="item"></session>
			</block>
			<view class="im-data-none" v-if="loadStatus">{{loadStatus}}</view>
		</view>
		<!-- 会话列表-end -->
	</view>
</template>

<script>
	export default {
		data() {
			return {
				keywords: '',
				sessionList: [],
				loadStatus: '加载中...',
				commonTips: ''
			}
		},
		onLoad(query) {
			this.keywords = query.keywords ? query.keywords:''
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow() {
			this.ws.checkNetwork(this)
		},
		methods: {
			search: function () {
				if (!this.keywords) {
					uni.showToast({
						title: '请输入关键词~',
						icon: 'none'
					})
					return ;
				}
				this.ws.pageFun(this.pageDataLoad, this);
			},
			pageDataLoad: function () {
				this.ws.send({
					c: 'Message',
					a: 'searchUser',
					data: this.keywords
				})
			},
			goToMessage: function(id) {
				uni.navigateTo({
					url: '/pages/message/message?id=' + id
				})
			}
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
</style>
