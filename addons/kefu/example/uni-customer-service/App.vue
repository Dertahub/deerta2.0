<script>
	var iosSessionId = 0;
	import Vue from 'vue'
	export default {
		onLaunch: function() {
			var that = this
			
			// #ifdef APP-PLUS
			plus.push.addEventListener('click', function(msg) {
				// console.log('用户点击了', msg)
				// 安卓离线通知被点击可能会触发两次click,第一次的msg.payload为莫名obj
				if ((msg.payload && Object.prototype.toString.call(msg.payload) !== '[object Object]') || iosSessionId) {
					let sessionShow = () => {
						let sessionId = (iosSessionId ? iosSessionId:msg.payload)
						if (parseInt(sessionId) == 0) {
							uni.switchTab({
								url: '/pages/session/session'
							})
							return ;
						}
						uni.navigateTo({
							url: '/pages/message/message?id=' + sessionId
						})
					}
					let pages = getCurrentPages(), page = pages[pages.length - 1]
					if (page && page.route == 'pages/session/session') {
						sessionShow()
					} else {
						that.ws.loadSessionReady = sessionShow
					}
				}
			}, false)
			
			let platform = that.ws.userPlatform ? that.ws.userPlatform:uni.getSystemInfoSync().platform
			if (platform == 'ios') {
				plus.push.addEventListener("receive", function(msg) {
					if ('ignore' == msg.payload) {
						
					} else {
						//接收透传消息
						iosSessionId = msg.payload;
						plus.push.createMessage(msg.content, 'ignore', {
							title: msg.title,
							cover: false
						});
					}
				}, false);
			}
			// #endif
			
			uni.onNetworkStatusChange(function (res) {
			    if (res.isConnected) {
					const userinfo = uni.getStorageSync('userinfo');
					if (userinfo) {
						that.ws.needReconnect = true
						that.ws.init(userinfo.token)
					}
				} else {
					if (that.ws.socketTask) {
						that.ws.socketTask.close()
						that.ws.socketOpen = false
					}
					that.ws.checkNetwork()
				}
			});
		},
		onShow: function(query) {
			// #ifdef APP-PLUS
			plus.runtime.setBadgeNumber(0);
			// #endif
			if (query.path != 'pages/user/login') {
				this.ws.pageRefresh.message = true
				this.ws.pageRefresh.session = true
				this.checkLogin()
			}
		},
		methods: {
			checkLogin: function () {
				const userinfo = uni.getStorageSync('userinfo');
				var valid = true;
				if (!userinfo || !userinfo.token) {
					valid = false;
				} else {
					let token = userinfo.token.split('|');
					let time = Date.parse(new Date()).toString();
					time = time.substr(0,10);
					// 减去一秒,防止刚好到时间造成发送了错误的请求
					if ((parseInt(token[2]) - 2) < parseInt(time)) {
						valid = false;
					}
				}
				
				if (!valid) {
					setTimeout(() => {
						this.ws.logout()
					}, 300)
				} else {
					this.ws.init(userinfo.token)
				}
			}
		}
	}
</script>

<style lang="scss">
	@import "uview-ui/index.scss";
	.user-status-0 {
	    color: #777777 !important;
	}
	.user-status-1 {
	    color: #8a6d3b !important;
	}
	.user-status-2 {
	    color: #a94442 !important;
	}
	.user-status-3 {
	    color: #3c763d !important;
	}
	.im-data-none {
		display: block;
		line-height: 100rpx;
		text-align: center;
		font-size: 28rpx;
		color: #999999;
	}
</style>