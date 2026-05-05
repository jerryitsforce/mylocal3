# Flows and Test Cases for Spin to Win
## Currenly only supports one event, don't use multiple events.

## Show Floating Button: Yes/No
Layout > Spin to Win Button > Show Spin to Win Button: Yes/No
=> use this field to determine if the button should be shown or not.

1. if No, then we don't show the button.
=> OK

2. if Yes/No, but user is not logged in, then we don't show the button.
=> OK

3. if Yes:
- If "Status" is Disable, don't show the button.
=> OK

- If "Status" is Enable, if total Prizes is 100%, show the button fixed at the bottom of all pages.
=> OK

- If "Status" is Enable, if Scheduled is Yes, and current date is not in the range of Scheduled, don't show the button.
=> OK

4. we can change button image.
Layout > Spin to Win Button > Spin to Win Image: ...
=> OK

## Type:
Layout > View: Pop up Dialog / New Page
=> use this field to determine how the Spin to Win will be displayed as a popup or a new page.

### Popup Type:
Layout > View: Pop up Dialog
=> Not supported yet, will be implemented later.

### Page Type:
Layout > View: New Page

5. if access to the Spin to Win page, and "Status" is Disable, redirect to home page.
=> OK

6. if access to the Spin to Win page, and user is not logged in, redirect to login page.
=> OK

7. if access to the Spin to Win page, and "Status" is Enable, user is logged in, scheduled is Yes, but current date is not in the range of Scheduled, redirect to home page.
=> OK

8. if access to the Spin to Win page, "Status" is Enable, user is logged in, prizes is enough 100%, scheduled is No, show the Spin to Win page.
=> OK

9. if access to the Spin to Win page, "Status" is Enable, user is logged in, prizes is enough 100%, scheduled is Yes, and current date is in the range of Scheduled, show the Spin to Win page.
=> OK

10. can set page url and redirect to it when clicked on Floating Button.
Layout > Campaign URL: ...
=> OK

11. can set page title and it will be displayed in the bar of the browser.
Layout > Campaign Title: ...
=> OK

12. can change the background mobile and desktop image of the spin to win page.
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Background Desktop | Background Mobile : ...
=> OK

13. can change page Title 1 (ex: 百萬名車)
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Information > Campaign Name: ...
=> OK

14. can change page Title 2 (ex: 大禮包)
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Information > Campaign Name 2: ...
=> OK

15. Main color of the roulette
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Main color of the roulette: ...
Layout > Main text color of the roulette: ...
=> OK

- we can set text color for this one too:
=> OK

16. Secondary color of the roulette
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Secondary color of the roulette: ...
Layout > Secondary text color of the roulette: ...
=> OK

- we can set text color for this one too:
=> OK

17. Base frame color 1
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Base frame color 1: ...
=> OK

18. Base frame color 2
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Base frame color 2: ...
=> OK

19. Base frame color 3
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Base frame color 3: ...
=> OK

20. Main color of the winning record
see slide 28 of this docs to see where will apply on FE: https://docs.google.com/presentation/d/1q4QGDGNJhLIq_Q4EMRXbgiBO9Ts01O1NVOKWLaPLpZg/edit?slide=id.g331edf1b19b_0_589#slide=id.g331edf1b19b_0_589
Layout > Main color of the winning record: ...
=> OK

21. can change Pin image of the roulette:
Layout > Spin Wheel Form > Pin image: ...
=> OK

22. can change bkg Go button:
Layout > Spin Wheel Form > Center Color: ...
=> OK

### Show chances to draw section (抽獎機會：1 次)
23. show chances to draw section when we got chances (抽獎機會：1 次):
=> OK

21. hide draw section when we have 0 chances (hide this one 抽獎機會：0 次):
=> OK

22. click on noti icon (!) to show popup P2_m.8.2.3_抽獎機會說明
=> OK

23. we can change title of popup P2_m.8.2.3_抽獎機會說明
Layout > Hint title: ...
=> OK

24. we can change content of popup P2_m.8.2.3_抽獎機會說明
Layout > Hint text: ...
=> OK

25. on popup P2_m.8.2.3_抽獎機會說明,  click on "去抽獎" button will trigger to show popup P2_m.8.2.4_抽獎確認
=> OK

26. on popup P2_m.8.2.4_抽獎確認, click on "再考慮一下" button will close the popup
=> OK

27. on popup P2_m.8.2.4_抽獎確認, click on "確認兌換" button, and if we set "Allow Redeem Point" to No:
- it will show message like P2_m.8.2.2_複製折扣碼成功, to inform user that we can't allow to redeem point. and close the popup P2_m.8.2.4_抽獎確認.
=> OK
=> message: This campaign doesn't allow point redemption (此活動不允許使用點數兌換抽獎次數。)

28. on popup P2_m.8.2.4_抽獎確認, click on "確認兌換" button, and if we set "Allow Redeem Point" to Yes, we don't have enough point:
- it will show popup P2_m.8.2.5_點數不足無法抽獎, and close the popup P2_m.8.2.4_抽獎確認.
=> OK

29. on popup P2_m.8.2.4_抽獎確認, click on "確認兌換" button, and if we set "Allow Redeem Point" to Yes, we have enough point:
- if success, close the popup P2_m.8.2.4_抽獎確認., and do the following:

29.1. it will show message like P2_m.8.2.2_複製折扣碼成功, to inform user that we got a chance to draw.
=> OK

29.2. it will update the chances to draw section to show the new chances (抽獎機會：1 次)
=> OK

29.3. it will trigger to draw the roulette and show the result.
=> OK

- if failed, close the popup P2_m.8.2.4_抽獎確認., show popup P2_m.8.2.5_點數不足無法抽獎, with error message in content.
=> OK

30. on popup P2_m.8.2.4_抽獎確認, checked this option: "直接抽獎，不再提示", click on "確認兌換" button, do the same as above (27-29), but in the next time:
- at 21. on popup P2_m.8.2.3_抽獎機會說明,  click on "去抽獎" button will not show popup P2_m.8.2.4_抽獎確認, but will trigger to show the roulette directly (the same as clicking on "確認兌換" button 23-25).
=> OK

### Show Redeem Section (P30抽獎1次)
Information > Allow Redeem Point: Yes/No

31. If "Allow Redeem Point" is No, we don't show redeem section (P30抽獎1次):
=> OK

32. If "Allow Redeem Point" is Yes, and there is chances, we don't show redeem section (P30抽獎1次):
=> OK

33. If "Allow Redeem Point" is Yes, and there is no chances:
- we show redeem section (P30抽獎1次):
=> OK

- click on noti icon (!) to show popup P2_m.8.2.3_抽獎機會說明, then do the same 18.:
=> OK


### Show Consolation Section (保底累計：3 / 4)
Consolation > Is Active: Yes/No

34. If "Is Active" is No, we don't show consolation section (保底累計：3 / 4):
=> ??

### Show Whell Section 
35. click on 'Go' button, if "Allow Redeem Point" is set to No, then it will show a message that inform that we can't allow to redeem point.
- it will show message like P2_m.8.2.2_複製折扣碼成功, to inform user that we can't allow to redeem point. and close the popup P2_m.8.2.4_抽獎確認.
=> OK

### Show Result Section


### Setting Campaign

30. Set "Status": Enable/Disable
Information > Status: Enable/Disable

- if "Status" is Enable, and Prizes is enough 100%, 
30. If "Scheduled" is No, we don't show campaign schedule section:
