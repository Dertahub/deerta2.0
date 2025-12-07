<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-action-sheet :list="sessionLongpressList" @click="sessionLongpressAction" v-model="sessionLongpressShow"></u-action-sheet>
		<u-select title="转接给" mode="single-column" :list="transferSelectList" v-model="transferShow" @confirm="transferSelectConfirm"></u-select>
		
		<!-- 顶部搜索栏-start -->
		<view class="search-box">
			<u-search @search="search()" @custom="search()" v-model="keywords" class="search-box-u-search" shape="square" placeholder="搜索其实很简单" :clearabled="true" :animation="true"></u-search>
		</view>
		<!-- 顶部搜索栏-end -->
		<!-- 会话列表-start -->
		<view class="session-list">
			<block v-for="(item, index) in sessionList" :key="item.id">
				<session @click.native="goToMessage(item.id)" @longpress.native="sessionLongpress(item.id, index)" :item="item"></session>
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
				sessionList: [],
				sessionLongpressList: [],
				sessionLongpressShow: false,
				keywords: '',
				loadStatus: '加载中...',
				transferShow: false,
				transferSelectList: [],
				transferUser: '',
				commonTips: '',
				pageDataLoadBool: false
			}
		},
		onLoad: function () {
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow() {
			var that = this
			if(this.ws.pageRefresh.session) {
				// 被其他页面通知刷新会话列表(更新列表中的好友备注等)
				this.ws.pageFun(this.pageDataLoad, this);
				this.ws.clearPageRefresh()
				this.ws.sessionShow = [] // 已经重载,无需执行messageShow中的方法
				return ;
			}
			
			if (this.ws.sessionShow.length) {
				for (let m in this.ws.sessionShow) {
					if (typeof this.ws.sessionShow[m] == 'function') {
						this.ws.sessionShow[m](that)
						this.ws.sessionShow[m] = null
					}
				}
			}
			
			if (!this.pageDataLoadBool) {
				return ;
			}
			
			this.ws.checkNetwork(this)
		},
		onPullDownRefresh: function () {
			this.ws.pageFun(this.pageDataLoad, this);
			this.ws.loadSessionReady = function () {
				this.pageThat.$refs.uToast.show({
					title: '刷新成功~',
					type: 'success'
				})
				uni.stopPullDownRefresh()
			}
		},
		methods: {
			pageDataLoad: function () {
				this.pageDataLoadBool = true
				this.ws.send({ c: 'Message', a: 'sessionList' })
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
			goToMessage: function (id) {
				uni.navigateTo({
					url: '/pages/message/message?id=' + id
				})
			},
			sessionLongpress: function (id, idx) {
				this.sessionLongpressShow = true
				this.sessionLongpressList = [{
					text: '转接会话',
					id: id,
					idx: idx
				},
				{
					text: '删除会话',
					color: '#f74c31',
					id: id,
					idx: idx
				}]
			},
			sessionLongpressAction: function (idx) {
				var that = this
				if (idx == 0) {
					that.transferShow = true
					that.transferUser = that.sessionList[that.sessionLongpressList[idx].idx].session_user
					that.ws.pageFun(() => {
						that.ws.send({ c: 'Message', a: 'actionSession', data: {
							action: 'transfer',
							session_user: that.sessionList[that.sessionLongpressList[idx].idx].session_user,
							platform: 'uni'
						}})
					}, that)
				} else if (idx == 1) {
					that.ws.pageFun(() => {
						that.ws.send({ c: 'Message', a: 'actionSession', data: {
							action: 'del',
							session_user: that.sessionList[that.sessionLongpressList[idx].idx].session_user,
							platform: 'uni'
						}})
					}, that);
				}
			},
			transferSelectConfirm: function (val) {
				var that = this
				that.ws.pageFun(() => {
					that.ws.send({ c: 'Message', a: 'actionSession', data: {
						action: 'transfer_done',
						csr: val[0].value,
						session_user: that.transferUser,
						platform: 'uni'
					}})
				}, that)
			}
		}
	}
</script>

<style lang="scss">
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
.search-box .message-menu {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-left: 20rpx;
}
.message-menu .message-menu-icon {
	padding: 6rpx;
	z-index: 1001;
	border-radius: 4px;
}
.message-menu-box {
	position: absolute;
	background: #FFFFFF;
	box-shadow: 0 0 20rpx rgba(0, 0, 0, .1);
	border-radius: 4px;
	z-index: 1001;
}
.popup-menu .popup-menu-item {
	padding: 30rpx 70rpx;
	text-align: center;
	border-bottom: 1px solid #F3F4F6;
}
.popup-menu .popup-menu-item:last-child {
	border: none;
}
.to-do .to-do-cell-item .to-do-cell-item-icon {
	margin-right: 10rpx;
}
.session-list-top-line {
	display: flex;
	align-items: center;
	justify-content: center;
}
.im-bg-grey {
	background-color: #EBEBEB;
	color: #FFFFFF;
}
.transfer-box {
	padding: 0 20rpx;
}
</style>
