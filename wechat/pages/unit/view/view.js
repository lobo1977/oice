//view.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

//获取应用实例
const app = getApp()

Page({
  data: {
    isLoading: false,
    isPullDown: false,
    isVideoPlay: false,
    info: {
      id: 0,
      title: '',
      building_name: '',
      building_id: 0,
      building_no: '',
      floor: 0,          // 楼层
      room: '',          // 房间号
      face: '',          // 朝向
      acreage: 0,        // 面积
      rent_sell: '',     // 租售
      rent_price: 0,     // 出租价格
      sell_price: 0,     // 出售价格
      decoration: '',    // 装修状况
      status: 0,         // 状态
      statusText: '',
      end_date_text: '', // 到日期
      rem: '',           // 备注
      key: '',
      isFavorite: false,
      allowNew: false,
      allowEdit: false,
      allowDelete: false,
      images: [],
      linkman: []
    },
    previewImages: []
  },
  
  onLoad(options) {
    let that = this
    app.globalData.refreshUnitView = false

    if (options.id) {
      that.data.info.id = options.id
    }
    if (options.key) {
      that.data.info.key = options.key
    }
    if (app.globalData.appUserInfo) {
      that.getView()
    } else {
      app.userLoginCallback = () => {
        that.getView()
      }
    }
  },

  onShow: function () {
    if (app.globalData.refreshUnitView) {
      this.getView()
      app.globalData.refreshUnitView = false
    }
  },
  
  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.data.isPullDown = true
      this.getView()
    }
    wx.stopPullDownRefresh()
  },
  
  // 转发事件
  onShareAppMessage(object) {
    let data = this.data.info
    let shareData = {
      title: data.building_name + ' ' + data.title,
      path: '/pages/unit/view/view?id=' + data.id + '&key=' + data.key
    }
    if (this.data.info.images.length) {
      shareData.imageUrl = app.globalData.serverUrl + '/' + this.data.info.images[0].msrc
    }
    return shareData
  },

  onVideoPlay() {
    this.setData({
      isVideoPlay: true
    })
  },

  onVideoStop() {
    this.setData({
      isVideoPlay: false
    })
  },
  
  // 预览图片
  bindViewImage(event) {
    let idx = event.currentTarget.dataset.data
    wx.previewMedia({
      sources: this.data.previewImages,
      current: idx
    })
  },
  
  bindPhoneCall(event) {
    let data = event.currentTarget.dataset.data
    if (data.mobile || data.tel) {
      wx.makePhoneCall({
        phoneNumber: data.mobile || data.tel
      })
    }
  },

  bindAddLinkman: function() {
    wx.navigateTo({
      url: '../../linkman/edit/edit?type=unit&oid=' + this.data.info.id
    })
  },
  
  bindCopyWeixin(event) {
    let data = event.currentTarget.dataset.data
    wx.setClipboardData({
      data: data.weixin || data.mobile
    })
  },

  bindViewNote: function() {
    wx.navigateTo({
      url: '../../web/web?url=' + app.globalData.serverUrl + '/index/unit/' + this.data.info.id
    })
  },

  bindEdit: function(event) {
    wx.navigateTo({
      url: '../edit/edit?id=' + this.data.info.id
    })
  },

  bindDelete: function() {
    Dialog.confirm({
      title: '删除确认',
      message: '确定要删除这个单元吗？',
    })
    .then(() => {
      this.remove()
    })
    .catch(() => {
    });
  },
  
  // 获取数据
  getView: function() {
    let that = this
    that.data.isLoading = true

    if (that.data.isPullDown == false) {
      wx.showLoading({
        title: '加载中',
      })
    } else {
      that.data.isPullDown = false
    }
    
    let url = 'unit/detail?id=' + that.data.info.id
    if (that.data.info.key) {
      url += '&key=' + that.data.info.key
    }
    
    app.get(url, (res) => {
      if (res.data) {
        let prevList = []
        if (res.data.videos.length) {
          res.data.videos.forEach(element => {
            prevList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'video',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        if (res.data.images.length) {
          res.data.images.forEach(element => {
            prevList.push({
              url: app.globalData.serverUrl + '/' + element.src,
              type: 'image',
              poster: app.globalData.serverUrl + '/' + element.msrc
            })
          })
        }
        that.setData({
          info: res.data,
          previewImages: prevList
        })
      }
    }, () => {
      that.data.isLoading = false
      wx.hideLoading()
    })
  },

  // 收藏
  favorite: function() {
    let that = this
    let url = that.data.info.isFavorite ? 'unit/unFavorite' : 'unit/favorite'
    wx.showLoading()
    app.post(url, {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        wx.showToast({
          icon: 'none',
          title: that.data.info.isFavorite ? '已取消收藏' : '已加入收藏',
          duration: 1000
        })
        that.setData({
          ['info.isFavorite']: that.data.info.isFavorite ? false : true
        })
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  remove: function() {
    let that = this
    wx.showLoading({
      title: '删除中',
    })
    app.post('unit/remove', {
      id: that.data.info.id
    }, (res) => {
      if (res.success) {
        app.globalData.refreshBuildingView = true
        wx.navigateBack()
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        })
      }
    }, () => {
      wx.hideLoading()
    })
  }
})