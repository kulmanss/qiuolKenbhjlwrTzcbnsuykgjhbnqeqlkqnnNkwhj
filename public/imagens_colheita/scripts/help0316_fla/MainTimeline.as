package help0316_fla
{
   import flash.display.*;
   import flash.events.*;
   
   public dynamic class MainTimeline extends MovieClip
   {
      
      public var closeButton1:SimpleButton;
      
      public var page2:MovieClip;
      
      public var page3:MovieClip;
      
      public var page1:MovieClip;
      
      public var page5:MovieClip;
      
      public var page4:MovieClip;
      
      public function MainTimeline()
      {
         super();
         addFrameScript(0,frame1);
      }
      
      internal function frame1() : *
      {
         page1.visible = true;
         page2.visible = false;
         page3.visible = false;
         page4.visible = false;
         page5.visible = false;
         page1.addEventListener("nextButtonClick",nextClick);
         page2.addEventListener("nextButtonClick",nextClick);
         page3.addEventListener("nextButtonClick",nextClick);
         page4.addEventListener("nextButtonClick",nextClick);
         page5.addEventListener("nextButtonClick",nextClick);
         page1.addEventListener("preButtonClick",preClick);
         page2.addEventListener("preButtonClick",preClick);
         page3.addEventListener("preButtonClick",preClick);
         page4.addEventListener("preButtonClick",preClick);
         page5.addEventListener("preButtonClick",preClick);
         closeButton1.visible = false;
      }
      
      public function nextClick(param1:Event) : void
      {
         page1.visible = false;
         page2.visible = false;
         page3.visible = false;
         page4.visible = false;
         page5.visible = false;
         if(param1.currentTarget == page1)
         {
            page2.visible = true;
         }
         else if(param1.currentTarget == page2)
         {
            page3.visible = true;
         }
         else if(param1.currentTarget == page3)
         {
            page4.visible = true;
         }
         else if(param1.currentTarget == page4)
         {
            page5.visible = true;
         }
      }
      
      public function closeButtonClick(param1:MouseEvent) : void
      {
         dispatchEvent(new Event("closeEvent"));
      }
      
      public function preClick(param1:Event) : void
      {
         page1.visible = false;
         page2.visible = false;
         page3.visible = false;
         page4.visible = false;
         page5.visible = false;
         if(param1.currentTarget == page5)
         {
            page4.visible = true;
         }
         else if(param1.currentTarget == page2)
         {
            page1.visible = true;
         }
         else if(param1.currentTarget == page3)
         {
            page2.visible = true;
         }
         else if(param1.currentTarget == page4)
         {
            page3.visible = true;
         }
      }
   }
}

