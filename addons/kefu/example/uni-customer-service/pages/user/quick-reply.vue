<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-action-sheet :list="actionList" @click="clickSwipeAction" v-model="actionShow"></u-action-sheet>
		<u-modal v-model="modelShow" :mask-close-able="true" :show-cancel-button="true" @confirm="delConfirm" :content="modelContent"></u-modal>
		<view
		:index="index"
		v-for="(item, index) in quickReply"
		:key="item.id"
		class="quick-reply-llst">
			<view @longpress="openSwipeAction(index)" class="quick-reply-item">
				<view class="quick-reply-wrap">
					<view class="u-line-2">{{ item.title }}</view>
					<view class="quick-reply-footer" :class="item.status == 1 ? 'quick-reply-footer-1':''">{{item.status == 1 ? '已启用':'已关闭'}}</view>
				</view>
			</view>
		</view>
		<view v-if="!quickReply.length" class="im-data-none">没有更多了~</view>
		<view class="footer-box"></view>
		<div class="quick-reply-button">
			<u-button @click="goAddQuickReply" type="primary">添加快捷回复</u-button>
		</div>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				quickReply: [],
				actionShow: false,
				actionIndex: 0,
				actionList: [{
					text: '编辑',
					color: '#007aff'
				},
				{
					text: '删除',
					color: '#f74c31'
				}],
				modelShow: false,
				modelContent: '',
				loadFastReply: null,
				commonTips: ''
			}
		},
		onShow: function(){
			this.ws.checkNetwork(this)
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onReachBottom: function () {
			if (this.loadFastReply) {
				this.loadFastReply.page++;
				let message = { c: 'Message', a: 'fastReply', data: this.loadFastReply }
				this.ws.pageFun(() => {
					this.ws.send(message);
				}, this);
				this.loadFastReply = false
			}
		},
		methods: {
			pageDataLoad: function () {
				let message = {
					c: 'Message',
					a: 'fastReply',
					data: {
						method: 'get',
						page: 1,
						platform: 'uni'
					}
				}
				this.ws.send(message);
			},
			openSwipeAction (index) {
				this.actionShow = true
				this.actionIndex = index
			},
			delConfirm: function () {
				var that = this, item = that.quickReply[that.actionIndex]
				that.ws.pageFun(function() {
					that.ws.send({
						c: 'Message',
						a: 'fastReply',
						data: {
							platform: 'uni',
							method: 'del',
							id: item.id
						}
					});
					that.actionIndex = 0;
				}, that)
			},
			clickSwipeAction (index) {
				var that = this, item = this.quickReply[this.actionIndex]
				if(index == 1) {
					that.modelShow = true
					that.modelContent = '确定删除快捷回复?'
				} else {
					uni.navigateTo({
						url: '/pages/user/edit-quick-reply?type=edit&id=' + item.id
					})
					that.actionIndex = 0
				}
				that.actionShow = false
			},
			goAddQuickReply: function() {
				uni.navigateTo({
					url: '/pages/user/edit-quick-reply?type=add'
				})
			}
		}
	}
</script>

<style>
.quick-reply-item {
	font-size: 30rpx;
	display: flex;
	align-items: center;
	padding: 30rpx;
	border-bottom: 1px solid #F2F3F4;
}
.quick-reply-footer {
	font-size: 28rpx;
	color: #999999;
	margin-top: 20rpx;
}
.quick-reply-footer-1 {
	color: #21E589;
}
.footer-box {
	height: 160rpx;
	display: block;
}
.quick-reply-button {
	position: fixed;
	bottom: 40rpx;
	width: 60vw;
	left: 20vw;
}
</style>
