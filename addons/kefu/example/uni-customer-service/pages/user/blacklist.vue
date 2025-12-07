<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-modal v-model="confirmShow" :content="confirmContent" :show-cancel-button="true" @confirm="confirmRelieve" confirm-text="解除"></u-modal>
		<view class="user-shield">
			<navigator :url="'/pages/user/info?type=user&id=' + item.user_id" v-for="(item, index) in users" :key="index" class="user-item">
				<image class="user-avatar" :src="item.avatar" mode="widthFix"></image>
				<view class="user-nickname">{{item.nickname}}</view>
				<u-button @click.stop="relieve(index)" class="user-button" size="mini">解除屏蔽</u-button>
			</navigator>
			<view v-if="!users.length" class="im-data-none">没有更多了~</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				users: [],
				relieveId: 0,
				confirmShow: false,
				confirmContent: '',
				commonTips: ''
			}
		},
		onLoad:function(){
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow:function(){
			this.ws.checkNetwork(this)
		},
		methods: {
			pageDataLoad: function () {
				let message = {
					c: 'Message',
					a: 'blacklistUser',
					data: {
						method: 'get',
						page: 1
					}
				}
				this.ws.send(message);
			},
			relieve: function(idx) {
				var user = this.users[idx]
				this.relieveId = user.id
				this.confirmShow = true
				this.confirmContent = '确认解除对`' + user.nickname + '`的屏蔽吗？'
			},
			confirmRelieve: function () {
				var that = this
				that.ws.pageFun(res => {
					that.ws.send({
						c: 'Message',
						a: 'blacklistUser',
						data: {
							id: that.relieveId,
							method: 'relieve'
						}
					})
				}, that)
			},
			onReachBottom: function () {
				var that = this
				if (that.loadData) {
					that.loadData.page++;
					let message = { c: 'Message', a: 'blacklistUser', data: that.loadData }
					that.ws.pageFun(function(){
						that.ws.send(message);
					}, that)
					that.loadData = false
				}
			},
		}
	}
</script>

<style>
.user-item {
	display: flex;
	align-items: center;
	padding: 20rpx;
}
.user-avatar {
	height: 90rpx;
	width: 90rpx;
	border-radius: 16rpx;
}
.user-nickname {
	display: block;
	padding-left: 16rpx;
	width: 62%;
	overflow: hidden;
	text-overflow:ellipsis;
	white-space: nowrap;
}
.user-button {
	margin-left: auto;
}
</style>
