const form = document.querySelector(".form"),
continueBtn = form.querySelector(".form-btn"),
errorText = document.querySelector(".form-container .error");

form.onsubmit = (e)=>{
    e.preventDefault();
}

continueBtn.onclick = ()=>{
    let xhr = new XMLHttpRequest(); 
    xhr.open("POST", form.getAttribute("action") , true);
    let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
    xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken);
    xhr.onload = ()=>{
      if(xhr.readyState === XMLHttpRequest.DONE){
          if(xhr.status === 200){
              let data = xhr.response;
              if(data === "success"){
                location.href="/taskManagement";
              }else{
                errorText.textContent = data;
              }
          }
      }
    }
    let formData = new FormData(form);
    xhr.send(formData);
}