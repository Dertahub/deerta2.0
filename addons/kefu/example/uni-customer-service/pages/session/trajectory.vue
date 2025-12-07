<template>
	<view class="body">
		<common :tips='commonTips'></common>
		<u-time-line v-for="(item, name, index) in trajectory" :key="index">
			<view class="u-time-line-title">{{name}}</view>
			<u-time-line-item v-for="(tr, idx) in item" :key="idx">
				<template v-slot:content>
					<view>
						<view class="u-order-desc">
							<span>{{tr.note}}</span>
							<span class="referrer" v-if="tr.log_type == 0">来路：{{tr.referrer}}</span>
						</view>
						<view class="u-order-time">{{tr.createtime}}</view>
					</view>
				</template>
			</u-time-line-item>
		</u-time-line>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				sessionUser: '',
				trajectory: [],
				chat_record_page: 'done',
				commonTips: ''
			}
		},
		onLoad(query) {
			this.id = query.id ? query.id : ''
			this.ws.pageFun(this.pageDataLoad, this);
		},
		onShow:function(){
			this.ws.checkNetwork(this)
		},
		onReachBottom: function () {
			var that = this
			if (that.chat_record_page == 'done') {
				return ;
			}
			// 加载轨迹记录
			var load_log = {
				c: 'Message',
				a: 'trajectory',
				data: {
					session_user: that.id,
					page: that.chat_record_page,
					platform: 'uni'
				}
			}
			that.ws.pageFun(() => {
				that.ws.send(load_log)
			}, that);
		},
		methods: {
			pageDataLoad: function() {
				var that = this
				let message = {
					c: 'Message',
					a: 'trajectory',
					data: {
						'session_user': that.id,
						'page': 1,
						'platform': 'uni'
					}
				}
				that.ws.send(message);
			}
		}
	}
</script>

<style>
.body {
	width: 90vw;
	margin: 0 auto;
}
.u-order-desc {
	word-wrap: break-word;
	word-break: break-all;
}
.referrer {
	display: block;
}
.u-time-line-title {
	height: 80rpx;
	line-height: 80rpx;
	font-weight: bold;
}
</style>
