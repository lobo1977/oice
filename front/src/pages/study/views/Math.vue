<template>
  <div>
    <flexbox :gutter="0">
        <flexbox-item :span="6">
          <group gutter="0" label-width="6.2em" label-margin-right="0">
            <x-input v-for="(item, index) in questionA" :key="index" :should-toast-error="false"
              :class="{ 'error':  showAnswer && item.input != item.answer, 'correct': showAnswer && item.input == item.answer }"
              :title="item.question" type="tel" pattern="[0-9]*" v-model="item.input" :max="3" :show-clear="false">
              <span slot="right" v-show="showAnswer">{{item.answer}}</span>
            </x-input>
          </group>
        </flexbox-item>
        <flexbox-item :span="6">
          <group gutter="0" label-width="7em" label-margin-right="0">
            <x-input v-for="(item, index) in questionB" :key="index" :should-toast-error="false"
              :class="{ 'error':  showAnswer && item.input != item.answer, 'correct': showAnswer && item.input == item.answer }"
              :title="item.question" type="tel" pattern="[0-9]*" v-model="item.input" :max="3" :show-clear="false">
              <span slot="right" v-show="showAnswer">{{item.answer}}</span>
            </x-input>
          </group>
        </flexbox-item>
    </flexbox>

    <actionsheet v-model="showMenu" :menus="menu" theme="android" @on-click-menu="menuClick">
    </actionsheet>

    <div v-transfer-dom>
      <popup v-model="showPrint" height="100%" style="overflow:auto;" @click.native="showPrint = false">
        <h3 style="margin-top:20px;margin-bottom:5px;text-align:center;">{{ menu[questionType] }}</h3>
        <h5 style="margin-bottom:20px;text-align:center;cursor:pointer;" 
          @click.stop="showDatePicker">{{ today }}</h5>
        <flexbox :gutter="0">
            <flexbox-item :span="6">
              <group gutter="0" label-width="10em" label-margin-right="0">
                <cell v-for="(item, index) in questionA" :key="index"
                 :title="item.question">
                </cell>
              </group>
            </flexbox-item>
            <flexbox-item :span="6">
              <group gutter="0" label-width="10em" label-margin-right="0">
                <cell v-for="(item, index) in questionB" :key="index"
                 :title="item.question">
                </cell>
              </group>
            </flexbox-item>
        </flexbox>
      </popup>
    </div>

    <div style="padding:15px">
      <x-button type="primary" @click.native="newQuestions">重新出题</x-button>
      <x-button type="warn" @click.native="showAnswer = !showAnswer">{{showAnswer ? '隐藏' : '显示'}}答案</x-button>
    </div>
  </div>
</template>

<script>
import { TransferDom } from 'vux'

export default {
  directives: {
    TransferDom
  },
  components: {
  },
  data () {
    return {
      today: '',
      questionType: 'addSub',
      showPrint: false,
      showAnswer: false,
      showMenu: false,
      menu: {
        addSub: '两位数和个位数加减法',
        addMore: '两位数和两位数加减法',
        multi: '九九乘法表'
      },
      questionA: [],
      questionB: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (localStorage.questionType) {
        vm.questionType = localStorage.questionType
      }
      vm.today = vm.formatDate(new Date())
      vm.newQuestions()
      if (vm.$isWechat()) {
        let shareLink = window.location.href
        let shareTitle = '小学霸'
        let shareDesc = '小学一年级数学作业'
        let shareImage = 'http://m.o-ice.com/static/img/study_logo.jpg'
        vm.$wechatShare(null, shareLink, shareTitle, shareDesc, shareImage)
      }
    })
  },
  methods: {
    propMenu () {
      this.showMenu = true
    },
    menuClick (menuKey, menuItem) {
      localStorage.questionType = menuKey
      this.questionType = menuKey
      this.newQuestions()
    },
    print () {
      this.showPrint = true
    },
    formatDate (date) {
      if (date) {
        return this.$dateFormat(date, 'YYYY年M月D日')
      } else {
        return this.$dateFormat(new Date(), 'YYYY年M月D日')
      }
    },
    newQuestions () {
      if (this.questionType === 'multi') {
        this.multiQuestions()
      } else if (this.questionType === 'addMore') {
        this.addMoreQuestions()
      } else {
        this.addSubQuestions()
      }
    },
    addSubQuestions () {
      let vm = this
      let num1, num2, num3, symbol1, symbol2, answer
      vm.questionA = []
      vm.questionB = []
      for (let i = 0; i < 10; i++) {
        num1 = vm.randomNum(15, 99)
        num2 = vm.randomNum(6, 9)
        num3 = vm.randomNum(3, 9)
        if (num1 < 18) {
          symbol1 = '+'
        } else {
          symbol1 = vm.randomSymbol()
        }
        symbol2 = vm.randomSymbol()
        answer = num1 + (symbol1 === '+' ? num2 : num2 * -1) + (symbol2 === '+' ? num3 : num3 * -1)
        vm.questionA.push({
          'question': `${num1} ${symbol1} ${num2} ${symbol2} ${num3} =`,
          'answer': answer,
          'input': ''
        })

        num1 = vm.randomNum(15, 99)
        num2 = vm.randomNum(6, 9)
        num3 = vm.randomNum(3, 9)
        if (num1 < 18) {
          symbol1 = '+'
        } else {
          symbol1 = vm.randomSymbol()
        }
        if (num2 < num3) {
          symbol2 = '+'
        } else {
          symbol2 = vm.randomSymbol()
        }
        let rightAnswer = num2 + (symbol2 === '+' ? num3 : num3 * -1)
        answer = num1 + (symbol1 === '+' ? rightAnswer : rightAnswer * -1)
        vm.questionB.push({
          'question': `${num1} ${symbol1} (${num2} ${symbol2} ${num3}) =`,
          'answer': answer,
          'input': ''
        })
      }
    },
    addMoreQuestions () {
      let vm = this
      let num1, num2, symbol1, answer
      vm.questionA = []
      vm.questionB = []
      for (let i = 0; i < 10; i++) {
        num1 = vm.randomNum(11, 99)
        symbol1 = vm.randomSymbol()
        if (symbol1 === '+') {
          num2 = vm.randomNum(11, 100 - num1)
        } else {
          num2 = vm.randomNum(11, num1)
        }
        answer = num1 + (symbol1 === '+' ? num2 : num2 * -1)
        vm.questionA.push({
          'question': `${num1} ${symbol1} ${num2} =`,
          'answer': answer,
          'input': ''
        })
      }
      for (let i = 0; i < 10; i++) {
        num1 = vm.randomNum(11, 99)
        symbol1 = vm.randomSymbol()
        if (symbol1 === '+') {
          num2 = vm.randomNum(11, 100 - num1)
        } else {
          num2 = vm.randomNum(11, num1)
        }
        answer = num1 + (symbol1 === '+' ? num2 : num2 * -1)
        vm.questionB.push({
          'question': `${num1} ${symbol1} ${num2} =`,
          'answer': answer,
          'input': ''
        })
      }
    },
    multiQuestions () {
      let vm = this
      let num1, num2, answer
      vm.questionA = []
      vm.questionB = []
      for (let i = 0; i < 10; i++) {
        num1 = vm.randomNum(2, 9)
        num2 = vm.randomNum(2, 9)
        answer = num1 * num2
        vm.questionA.push({
          'question': `${num1} × ${num2} =`,
          'answer': answer,
          'input': ''
        })
      }
      for (let i = 0; i < 10; i++) {
        num1 = vm.randomNum(2, 9)
        num2 = vm.randomNum(2, 9)
        answer = num1 * num2
        vm.questionB.push({
          'question': `${num1} × ${num2} =`,
          'answer': answer,
          'input': ''
        })
      }
    },
    randomNum (minNum, maxNum) {
      switch (arguments.length) {
        case 1:
          return parseInt(Math.random() * minNum + 1, 10)
        case 2:
          return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10)
        default:
          return 0
      }
    },
    randomSymbol () {
      let num = this.randomNum(0, 9)
      if (num % 2 === 0) return '+'
      else return '-'
    },
    showDatePicker () {
      let vm = this
      vm.$vux.datetime.show({
        cancelText: '取消',
        confirmText: '确定',
        format: 'YYYY年M月D日',
        value: vm.today,
        onConfirm (val) {
          vm.today = val
        }
      })
    }
  },
  filters: {
  }
}
</script>

<style lang="less">
  .error input {
    color:red;
  }
  .correct input {
    color:#1AAD19;
  }
</style>