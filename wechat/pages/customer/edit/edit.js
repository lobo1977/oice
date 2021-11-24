// pages/customer/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';
import { areaList } from '@vant/area-data';

const app = getApp()

Page({
  data: {
    id: 0,
    areaList,
    showArea: false,
    demand:[],
    showDemand: false,
    lease_buy: [ {name: '租赁'}, {name: '购买'}],
    showLeaseBuy: false,
    district: [],
    showDistrict: false,
    showSettleDate: false,
    numberSettleDate: Date.now(),
    showEndDate: false,
    numberEndDate: Date.now(),
    status: [ 
      {name: '潜在', value: 0}, 
      {name: '跟进', value: 1}, 
      {name: '看房', value: 2}, 
      {name: '洽谈', value: 3}, 
      {name: '成交', value: 4}, 
      {name: '失败', value: 5}, 
      {name: '名录', value: 6}
    ],
    showStatus: false,
    info: {
      __token__: '',
      customer_name: '',    // 名称
      tel: '',              // 直线电话
      city: '',      // 城市
      area: '',      // 城区
      area_code: '110105',  // 行政区划代码
      address: '',          // 地址
      demand: '',           // 需求项目
      lease_buy: '',        // 租赁/购买
      district: '',         // 意向商圈
      min_acreage: null,    // 最小面积(平方米)
      max_acreage: null,    // 最大面积(平方米)
      budget: '',           // 预算
      settle_date: '',      // 入驻日期
      current_area: null,   // 在驻面积
      end_date: '',         // 到期日
      remind: 8,            // 到期提醒（提前月份数）
      rem: '',              // 项目说明
      status: 6,            // 状态
      company_id: 0,        // 所属企业
      share: 0,
      linkman: '',          // 联系人
      clash: 0              // 撞单客户
    },
    is_name_empty: false,
    name_error: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      wx.setNavigationBarTitle({
        title: '修改客户信息'
      })
      that.setData({
        id: options.id
      })
    } else {
      wx.setNavigationBarTitle({
        title: '添加客户'
      })
    }
    if (app.globalData.appUserInfo) {
      that.getData()
    } else {
      app.userLoginCallback = () => {
        that.getData()
      }
    }
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
  },

  // 获取数据
  getData: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'customer/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        let info = that.data.info
        for (let item in info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            info[item] = res.data[item]
          }
        }
        if (res.data.settle_date) {
          info.settle_date = app.formatTime(Date.parse(res.data.settle_date.replace(/-/g, '/')), 'yyyy-MM-dd')
          that.setData({
            numberSettleDate: Date.parse(res.data.settle_date.replace(/-/g, '/'))
          })
        }
        if (res.data.end_date) {
          info.end_date = app.formatTime(Date.parse(res.data.end_date.replace(/-/g, '/')), 'yyyy-MM-dd')
          that.setData({
            numberEndDate: Date.parse(res.data.end_date.replace(/-/g, '/'))
          })
        }

        let arrDistrict = []
        let arrDemand = []
        app.globalData.area.forEach(element => {
          if (element.id != 'all') {
            if (element.children && element.children.length) {
              let dataDistrict = info.district ? info.district : ''
              element.children.forEach(d => {
                if (d.id != '') {
                  arrDistrict.push( { text: d.text, type: dataDistrict.indexOf(d.text) >= 0 ? 'success' : 'default' })
                }
              })
            }
          }
        })

        app.globalData.buildingType.forEach(element => {
          if (element.text != '类别') {
            arrDemand.push(element.text)
          }
        })

        that.setData({
          info: info,
          demand: arrDemand,
          district: arrDistrict
        })
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          app.goBack()
        })
      }
    }, () => {
      wx.hideLoading()
    })
  },

  onNameInput: function(event) {
    this.data.info.customer_name = event.detail
    if (this.data.info.customer_name && this.data.is_name_empty) {
      that.setData({
        is_name_empty: false,
        name_error: ''
      })
    }
  },

  onTelInput: function(event) {
    this.data.info.tel = event.detail
  },

  onLinkmanInput: function(event) {
    this.data.info.linkman = event.detail
  },

  onAddressInput: function(event) {
    this.data.info.address = event.detail
  },

  bindSelectArea: function() {
    this.setData({
      showArea: true
    })
  },

  onAreaPickerClose: function() {
    this.setData({
      showArea: false
    })
  },

  onAreaSelected: function(event) {
    let selected = event.detail.values
    let city = '', area = '', code = ''
    if (selected[2] && selected[2].code) {
      city = selected[1].name
      area = selected[2].name
      code = selected[2].code
    } else if (selected[1] && selected[1].code) {
      city = selected[1].name
      code = selected[1].code
    } else if (selected[0] && selected[0].code) {
      code = selected[0].code
    }
    this.setData({
      ['info.city']: city,
      ['info.area']: area,
      ['info.area_code']: code,
      showArea: false
    })
  },

  bindSelectDemand: function() {
    this.setData({
      showDemand: true
    })
  },

  onDemandPickerClose: function() {
    this.setData({
      showDemand: false
    })
  },

  onDemandSelected: function(event) {
    this.setData({
      ['info.demand']: event.detail.value,
      showDemand: false
    })
  },

  bindSelectLeaseBuy: function() {
    this.setData({
      showLeaseBuy: true
    })
  },

  onLeaseBuyClose: function() {
    this.setData({
      showLeaseBuy: false
    })
  },

  onLeaseBuySelect: function(event) {
    this.setData({
      ['info.lease_buy']: event.detail.name,
      showLeaseBuy: false
    })
  },

  bindSelectDistrict: function() {
    this.setData({
      showDistrict: true
    })
  },

  onDistrictClose: function() {
    let strDistrict = ''
    this.data.district.forEach(d => {
      if (d.type == 'success') {
        if (strDistrict.length) {
          strDistrict += ','
        }
        strDistrict+= d.text
      }
    })
    this.setData({
      showDistrict: false,
      ['info.district']: strDistrict
    })
  },

  onDistrictTap: function(event) {
    let idx = event.target.dataset.data
    let type = this.data.district[idx].type
    this.setData({
      ['district[' + idx + '].type']: type == 'success' ? 'default' : 'success'
    })
  },

  onMinAcreageInput: function(event) {
    this.data.info.min_acreage = event.detail
  },

  onMaxAcreageInput: function(event) {
    this.data.info.max_acreage = event.detail
  },

  onBudgeInput: function(event) {
    this.data.info.budge = event.detail
  },

  onCurrentAreaInput: function(event) {
    this.data.info.current_area = event.detail
  },

  bindSelectSettleDate: function() {
    this.setData({
      showSettleDate: true
    })
  },

  onSettleDateConfirm: function(event) {
    this.setData({
      ['info.settle_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showSettleDate: false
    })
  },

  onSettleDateClose: function() {
    this.setData({
      showSettleDate: false
    })
  },

  bindSelectEndDate: function() {
    this.setData({
      showEndDate: true
    })
  },

  onEndDateConfirm: function(event) {
    this.setData({
      ['info.end_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showEndDate: false
    })
  },

  onEndDateClose: function() {
    this.setData({
      showEndDate: false
    })
  },

  onRemindChange: function(event) {
    this.data.info.remind = event.detail
  },

  onRemInput: function(event) {
    this.data.info.rem = event.detail
  },

  onShareChange: function(event) {
    this.setData({
      ['info.share']: event.detail ? 1 : 0
    })
  },

  bindSelectStatus: function() {
    this.setData({
      showStatus: true
    })
  },

  onStatusClose: function() {
    this.setData({
      showStatus: false
    })
  },

  onStatusSelect: function(event) {
    this.setData({
      ['info.status']: event.detail.value,
      showStatus: false
    })
  },

  bindSave: function() {
    let that = this
    if (!that.data.info.customer_name) {
      that.setData({
        is_name_empty: true,
        name_error: '请输入客户名称'
      })
      return
    } else if (that.data.is_name_empty) {
      that.setData({
        is_name_empty: false,
        name_error: ''
      })
    }

    wx.showLoading({
      title: '保存中',
    })

    app.post('customer/edit?id=' + that.data.id, that.data.info, (res) => {
      if (res.success) {
        app.globalData.refreshCustomer = true
        if (res.message) {
          Dialog.alert({
            title: '保存成功',
            message: res.message
          }).then(() => {
            app.getMyCustomer()
            app.globalData.refreshCustomerView = true
            if (that.data.id == 0) {
              let id = res.data
              wx.redirectTo({
                url: '../view/view?id=' + id
              })
            } else {
              app.goBack()
            }
          })
        } else if (that.data.id == 0) {
          let id = res.data
          wx.redirectTo({
            url: '../view/view?id=' + id
          })
        } else {
          app.goBack()
        }
      } else if (res.data) {
        if (res.data.token) {
          that.data.info.__token__ = res.data.token
        }
        if (res.data.confirm && res.data.clash) {
          Dialog.confirm({
            title: '撞单提醒',
            message: res.message
          }).then(() => {
            that.data.info.clash = res.data.clash
            that.bindSave()
          })
        } else {
          Dialog.alert({
            title: res.data.clash ? '撞单提醒' : '发生错误',
            message: res.message
          }).then(() => {
            if (res.data.clash) {
              let id = res.data.clash
              wx.redirectTo({
                url: '../view/view?id=' + id
              })
            }
          })
        }
      } else if (res.message) {
        wx.showToast({
          icon: 'none',
          title: res.message,
          duration: 2000
        })
      } else {
        wx.showToast({
          icon: 'none',
          title: '操作失败，系统异常',
          duration: 2000
        })
      }
    }, () => {
      wx.hideLoading()
    })
  }
})