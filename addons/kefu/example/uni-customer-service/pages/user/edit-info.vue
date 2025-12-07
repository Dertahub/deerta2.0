<template>
	<view>
		<u-toast ref="uToast" />
		<common :tips='commonTips'></common>
		<u-form class="info-form">

			<u-form-item v-if="showAvatarUpload" label-width="180" label="头　　像">
				<view>
					<u-upload ref="uUpload" @on-remove="avatarOnRemove" @on-change="avatarOnChange" :size-type="['compressed']"
					name="file" width="160" height="160" :form-data="uploadFormData" :action="uploadAction" :max-count="avatarFileNumber"
					:file-list="avatarFileList"></u-upload>
				</view>
			</u-form-item>

			<block v-for="(item, index) in detail" :key="index">
				<u-form-item v-if="item.type == 'input' || item.type == 'textarea'" label-width="180"
					:label="item.title">
					<u-input :type="item.type" v-model="item.value" :placeholder="'请输入' + (item.placeholderTitle ? item.placeholderTitle:item.title)" />
				</u-form-item>
				<u-form-item v-if="item.type == 'select'" label-width="180" :label="item.title">
					<u-select :default-value="item.value" v-model="item.show" mode="single-column" :list="item.data"
						@confirm="confirmSelect"></u-select>
					<view @click="openSelect(index)">{{item.data[item.value[0]].label}}</view>
				</u-form-item>
				<u-form-item v-if="item.type == 'date'" label-width="180" :label="item.title">
					<u-picker :default-time="item.value[0] + '-' + item.value[1] + '-' + item.value[2]"
						v-model="item.show" mode="time" @confirm="confirmData"></u-picker>
					<view @click="openSelect(index, index)">
						{{item.value[0] + '-' + item.value[1] + '-' + item.value[2]}}
					</view>
				</u-form-item>
			</block>
			<u-button v-if="detail.length" :loading="submitButtonStatus" :disabled="submitButtonStatus"
				class="submit-button" type="primary" @click="submit">{{submitButtonText}}</u-button>
		</u-form>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: 0,
				detail: [],
				openSelectName: false,
				showAvatarUpload: false,
				uploadAction: '',
				uploadFormData: new Object(),
				avatarFileList: [],
				userToken: '',
				avatarFileNumber: 2,
				newAvatar: false,
				submitButtonStatus: false,
				submitButtonText: '保存',
				commonTips: ''
			}
		},
		onLoad: function(query) {
			this.id = query.id ? query.id : 0
			let userinfo = uni.getStorageSync('userinfo');
			this.userToken = userinfo.token
		},
		onShow:function(){
			this.ws.checkNetwork(this)
		},
		onReady: function(query) {
			var that = this
			that.uploadAction = that.ws.buildUrl('upload', that.userToken)
			
			// time需要在ready赋值
			this.ws.pageFun(this.pageDataLoad, this);
		},
		methods: {
			openSelect: function(index, name = false) {
				this.detail[index].show = true
				this.openSelectName = name ? name : false
			},
			confirmData: function(value) {
				this.detail[this.openSelectName].value = [
					value.year,
					value.month,
					value.day
				]
			},
			confirmSelect: function(value) {
				var valueIndex = 0,
					data = this.detail[value[0].extra].data;
				for (var i = 0; i < data.length; i++) {
					if (data[i].value == value[0].value) {
						valueIndex = i
					}
				}
				this.detail[value[0].extra].value = [valueIndex]
			},
			pageDataLoad: function() {
				var that = this
				this.ws.send({ c: 'Message', a: 'getInfo', data: {
					id: this.id,
					method: 'edit'
				}})
				
				that.ws.send({
					c: 'Message',
					a: 'getUploadMultipart'
				});
			},
			avatarOnSuccess: function(data) {
				this.newAvatar = this.ws.imgUrl(data.data.url)
				if (this.avatarFileNumber >= 2) {
					this.$refs.uUpload.clear();
					this.avatarFileList = [{
						url: this.newAvatar
					}]
					this.avatarFileNumber = 1
				}
			},
			avatarOnChange: function(res, index, lists) {
				res = JSON.parse(res.data);
				if (res.code == 1) {
					this.avatarOnSuccess(res)
				} else {
					this.ws.pageFun(() => {
						that.ws.send({
							c: 'Message',
							a: 'getUploadMultipart'
						});
					}, this)
					
					this.$refs.uUpload.remove(index);
					
					uni.showModal({
						title: '温馨提示',
						content: res.msg,
						showCancel: false
					})
				}
			},
			avatarOnRemove: function() {
				this.avatarFileNumber = 1
			},
			submit: function() {
				var that = this
				that.submitButtonStatus = true
				var values = {};
				for (var i = 0; i < that.detail.length; i++) {
					if (that.detail[i].type == 'date') {
						values[that.detail[i].name] = that.detail[i].value[0] + '-' + that.detail[i].value[1] + '-' +
							that.detail[i].value[2]
					} else if (that.detail[i].type == 'select') {
						values[that.detail[i].name] = that.detail[i].data[that.detail[i].value[0]].value
					} else {
						values[that.detail[i].name] = that.detail[i].value
					}
				}

				if (that.newAvatar) {
					values.avatar = that.newAvatar
				}
				
				if (!values['nickname']) {
					that.$refs.uToast.show({
						title: '请输入正确的昵称~',
						type: 'error'
					})
					return;
				}
				
				values.method = 'post-user-edit'
				values.id = that.id
				var message = {
					c: 'Message',
					a: 'getInfo',
					data: values
				}

				that.ws.pageFun(function() {
					that.ws.send(message);
					that.ws.showMsgCallback = function() {
						setTimeout(function() {
							that.submitButtonStatus = false
							that.ws.pageRefresh.info = true
							that.ws.pageRefresh.addressList = true
							uni.navigateBack({
								delta: 1
							})
						}, 2000)
					}
				}, that)
			}
		}
	}
</script>

<style>
	page {
		background: #FFFFFF;
	}
	.info-form {
		display: block;
		width: 92vw;
		margin: 0 auto;
	}

	.submit-button {
		width: 60vw;
		display: block;
		margin: 40rpx auto;
	}
</style>
