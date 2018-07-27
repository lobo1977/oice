import Vue from 'vue'
import Router from 'vue-router'
import Math from '../views/Math'

Vue.use(Router)

export default new Router({
  base: '/study/',
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'Home',
      redirect: '/math'
    }, {
      path: '/math',
      name: 'Math',
      meta: {
        title: '数学作业',
        hideBar: true
      },
      component: Math
    }, {
      path: '*',
      meta: {
        hideBar: true
      },
      component: function (resolve) {
        require(['@/components/404.vue'], resolve)
      }
    }
  ]
})
